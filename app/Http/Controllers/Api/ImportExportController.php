<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImportExportService;
use Illuminate\Http\Request;

class ImportExportController extends Controller
{
    public function __construct(private ImportExportService $importExportService)
    {}

    public function export(int $supplierId)
    {
        $data = $this->importExportService->exportBySupplier($supplierId);
        return response()->json($data);
    }

    public function import(Request $request, int $supplierId)
    {
        $request->validate([
            'layups' => 'required|array',
            'layups.*.name' => 'required|string',
            'layups.*.layers' => 'present|array',
            'layups.*.layers.*.layer_order' => 'required|integer',
            'layups.*.layers.*.thickness' => 'required|numeric',
            'layups.*.layers.*.width' => 'required|numeric',
            'layups.*.layers.*.angle' => 'required|numeric',
            'strategy' => 'sometimes|string|in:overwrite,skip'
        ]);

        $strategy = $request->input('strategy', 'overwrite');
        
        $results = $this->importExportService->importForSupplier($supplierId, $request->all(), $strategy);
        
        return response()->json([
            'message' => 'Import completed',
            'results' => $results
        ]);
    }
}
