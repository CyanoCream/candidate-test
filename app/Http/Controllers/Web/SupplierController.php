<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SupplierService;
use App\Services\ImportExportService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(
        private SupplierService $supplierService,
        private ImportExportService $importExportService
    ) {}

    public function index(Request $request)
    {
        $search = $request->input('search');
        $suppliers = $this->supplierService->getAllSuppliers($search);
        return view('suppliers.index', compact('suppliers', 'search'));
    }

    public function exportCsv(Request $request)
    {
        $search = $request->input('search');
        $suppliers = $this->supplierService->getAllSuppliers($search);

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="suppliers.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0'
        ];

        $callback = function () use ($suppliers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Total Layups', 'Created Date']);

            foreach ($suppliers as $supplier) {
                fputcsv($file, [
                    $supplier->id,
                    $supplier->name,
                    $supplier->layups_count ?? 0,
                    $supplier->created_at ? $supplier->created_at->format('Y-m-d H:i:s') : ''
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function show(int $id)
    {
        $supplier = $this->supplierService->getSupplierById($id);
        $layups = $supplier->layups()->with('layers')->get();
        return view('suppliers.show', compact('supplier', 'layups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $this->supplierService->createSupplier($validated);
        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function destroy(int $id)
    {
        $this->supplierService->deleteSupplier($id);
        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
    }

    public function export(Request $request, int $id)
    {
        $data = $this->importExportService->exportBySupplier($id);
        
        $type = $request->query('type', 'json');

        if ($type === 'csv') {
            $filename = "supplier_{$id}_export.csv";
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];
    
            $callback = function () use ($data) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['layup_name', 'layer_order', 'thickness', 'width', 'angle']);
                
                foreach ($data['layups'] as $layup) {
                    foreach ($layup['layers'] as $layer) {
                        fputcsv($file, [
                            $layup['name'],
                            $layer['layer_order'] ?? '',
                            $layer['thickness'] ?? '',
                            $layer['width'] ?? '',
                            $layer['angle'] ?? ''
                        ]);
                    }
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }

        $filename = "supplier_{$id}_export.json";
        return response()->json($data)->setEncodingOptions(JSON_PRETTY_PRINT)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function import(Request $request, int $id)
    {
        $request->validate([
            'import_file' => 'required|file',
            'strategy' => 'required|in:overwrite,skip,manual'
        ]);

        $extension = $request->file('import_file')->getClientOriginalExtension();
        $fileContent = file_get_contents($request->file('import_file')->getRealPath());
        $data = [];

        if (strtolower($extension) === 'csv') {
            $rows = array_map('str_getcsv', explode("\n", trim($fileContent)));
            $header = array_shift($rows);
            $layupsMap = [];
            foreach ($rows as $row) {
                if (count($row) < 5) continue;
                // Build assoc taking care of differing lengths
                $rowAssoc = [];
                foreach ($header as $idx => $colName) {
                    $rowAssoc[trim($colName)] = isset($row[$idx]) ? trim($row[$idx]) : null;
                }

                $layupName = $rowAssoc['layup_name'] ?? null;
                if (!$layupName) continue;
                
                if (!isset($layupsMap[$layupName])) {
                    $layupsMap[$layupName] = [
                        'name' => $layupName,
                        'species_grade' => $rowAssoc['species_grade'] ?? 'Mixed',
                        'revision' => $rowAssoc['revision'] ?? '1.0',
                        'status' => $rowAssoc['status'] ?? 'active',
                        'layers' => []
                    ];
                }
                
                $layupsMap[$layupName]['layers'][] = [
                    'layer_order' => $rowAssoc['layer_order'] ?? 0,
                    'thickness' => $rowAssoc['thickness'] ?? 0,
                    'width' => $rowAssoc['width'] ?? 0,
                    'angle' => $rowAssoc['angle'] ?? 0,
                ];
            }
            $data = ['layups' => array_values($layupsMap)];
        } else {
            $data = json_decode($fileContent, true);
        }

        if (!$data || !isset($data['layups'])) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Invalid file format. Expected layups.'], 400);
            }
            return back()->with('error', 'Invalid format. Expected layups array.');
        }

        $strategy = $request->input('strategy', 'overwrite');
        $isDryRun = $request->boolean('dry_run', false);
        
        $results = $this->importExportService->importForSupplier($id, $data, $strategy, $isDryRun);

        if ($request->expectsJson()) {
            if ($strategy === 'manual') {
                $importId = uniqid('import_');
                session()->put("import_{$importId}", [
                    'supplier_id' => $id,
                    'payload' => $results['manual_payload']
                ]);
                return response()->json([
                    'success' => true,
                    'is_manual' => true,
                    'import_id' => $importId,
                    'payload' => $results['manual_payload'],
                    'conflicts' => $results['conflicts']
                ]);
            }

            return response()->json([
                'success' => true,
                'is_manual' => false,
                'message' => "Import " . ($isDryRun ? "Simulation" : "") . " completed. Created {$results['layups_created']} layups, {$results['layers_created']} layers. Updated {$results['layers_updated']} layers, Skipped {$results['layers_skipped']} layers.",
                'results' => $results
            ]);
        }

        if ($strategy === 'manual') {
            $importId = uniqid('import_');
            session()->put("import_{$importId}", [
                'supplier_id' => $id,
                'payload' => $results['manual_payload']
            ]);
            return redirect()->route('suppliers.conflicts', $importId);
        }

        return back()->with('success', "Import completed. Created {$results['layups_created']} layups, {$results['layers_created']} layers.");
    }

    public function conflicts(string $importId)
    {
        $sessionData = session()->get("import_{$importId}");
        if (!$sessionData) {
            return redirect()->route('suppliers.index')->with('error', 'Import session expired or not found.');
        }

        return view('suppliers.conflicts', [
            'importId' => $importId,
            'supplierId' => $sessionData['supplier_id'],
            'payload' => $sessionData['payload']
        ]);
    }

    public function resolveConflicts(Request $request, string $importId)
    {
        $sessionData = session()->get("import_{$importId}");
        if (!$sessionData) {
            return redirect()->route('suppliers.index')->with('error', 'Import session expired or not found.');
        }

        $resolutions = $request->input('resolutions', []);
        // Process resolutions and rebuild the data payload for a forced overwrite import...
        // Actually, since this is a UI prototype, we can assume the user made the choices
        // and we'll process the resolutions simply.
        
        // For actual implementation, we map over the original manual_payload, apply the resolution ('incoming' or 'existing')
        // and then feed it back into importForSupplier using 'overwrite'.
        
        $finalLayups = [];
        foreach ($sessionData['payload'] as $layupIdx => $layup) {
            $finalLayers = [];
            foreach ($layup['layers'] as $layerIdx => $layer) {
                if ($layer['status'] === 'conflict') {
                    $choice = $resolutions["{$layupIdx}_{$layerIdx}"] ?? 'existing'; // Default keep existing
                    if ($choice === 'incoming') {
                        $finalLayers[] = $layer['incoming'];
                    } else {
                        $finalLayers[] = $layer['existing'];
                    }
                } else {
                    $finalLayers[] = $layer['incoming']; // unchanged or new
                }
            }
            $finalLayups[] = [
                'name' => $layup['name'],
                'layers' => $finalLayers
            ];
        }

        // Run final import with 'overwrite' since we selected exactly what we wanted!
        $results = $this->importExportService->importForSupplier($sessionData['supplier_id'], ['layups' => $finalLayups], 'overwrite');
        
        session()->forget("import_{$importId}");

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Import completed after manual resolution. Created {$results['layups_created']} layups, {$results['layers_created']} layers."
            ]);
        }

        return redirect()->route('suppliers.show', $sessionData['supplier_id'])
            ->with('success', "Import completed after manual resolution. Created {$results['layups_created']} layups, {$results['layers_created']} layers. Updated {$results['layers_updated']} layers.");
    }

    public function downloadTemplate(Request $request)
    {
        $type = $request->query('type', 'csv');
        if ($type === 'json') {
            $data = [
                'layups' => [
                    [
                        'name' => 'CLT-5-Template',
                        'species_grade' => 'Mixed',
                        'revision' => '1.0',
                        'status' => 'active',
                        'layers' => [
                            ['layer_order' => 1, 'thickness' => 30, 'width' => 1200, 'angle' => 0],
                            ['layer_order' => 2, 'thickness' => 20, 'width' => 1200, 'angle' => 90]
                        ]
                    ]
                ]
            ];
            return response()->json($data)->setEncodingOptions(JSON_PRETTY_PRINT)
                ->header('Content-Disposition', 'attachment; filename="import_template.json"');
        }

        // CSV
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="import_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['layup_name', 'layer_order', 'thickness', 'width', 'angle', 'species_grade', 'revision', 'status']);
            fputcsv($file, ['CLT-5-Template', 1, 30, 1200, 0, 'Mixed', '1.0', 'active']);
            fputcsv($file, ['CLT-5-Template', 2, 20, 1200, 90, 'Mixed', '1.0', 'active']);
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}
