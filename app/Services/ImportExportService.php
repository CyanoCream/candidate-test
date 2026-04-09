<?php

namespace App\Services;

use App\Interfaces\SupplierRepositoryInterface;
use App\Interfaces\CltLayupRepositoryInterface;
use App\Interfaces\CltLayerRepositoryInterface;
use App\Models\CltLayup;
use Illuminate\Support\Facades\DB;

class ImportExportService
{
    public function __construct(
        private SupplierRepositoryInterface $supplierRepository,
        private CltLayupRepositoryInterface $layupRepository,
        private CltLayerRepositoryInterface $layerRepository
    ) {}

    public function exportBySupplier(int $supplierId): array
    {
        $supplier = $this->supplierRepository->getById($supplierId);
        $layups = $this->layupRepository->getAllBySupplier($supplierId);
        
        $layupsData = [];
        foreach ($layups as $layup) {
            $layers = $this->layerRepository->getAllByLayup($layup->id);
            $layupsData[] = [
                'name' => $layup->name,
                'layers' => $layers->map(function ($layer) {
                    return [
                        'layer_order' => $layer->layer_order,
                        'thickness' => $layer->thickness,
                        'width' => $layer->width,
                        'angle' => $layer->angle,
                    ];
                })->toArray(),
            ];
        }

        return [
            'supplier_name' => $supplier->name,
            'layups' => $layupsData,
        ];
    }

    public function importForSupplier(int $supplierId, array $importData, string $conflictStrategy = 'overwrite', bool $isDryRun = false)
    {
        return DB::transaction(function () use ($supplierId, $importData, $conflictStrategy, $isDryRun) {
            $supplier = $this->supplierRepository->getById($supplierId);
            
            $results = [
                'layups_created' => 0,
                'layers_created' => 0,
                'layers_updated' => 0,
                'layers_skipped' => 0,
                'conflicts' => [],
                'manual_payload' => [] // Stores pending resolutions
            ];

            $existingLayups = $this->layupRepository->getAllBySupplier($supplierId)->keyBy('name');

            foreach ($importData['layups'] as $layupData) {
                $layupName = $layupData['name'];
                
                // Track objects for manual serialization
                $manualLayup = ['name' => $layupName, 'layers' => []];

                if ($existingLayups->has($layupName)) {
                    $layup = $existingLayups->get($layupName);
                    $manualLayup['id'] = $layup->id;
                    $manualLayup['status'] = 'existing';
                } else {
                    if ($conflictStrategy !== 'manual') {
                        $layup = $this->layupRepository->create([
                            'supplier_id' => $supplierId,
                            'name' => $layupName,
                            'species_grade' => $layupData['species_grade'] ?? 'Mixed',
                            'revision' => $layupData['revision'] ?? '1.0',
                            'status' => $layupData['status'] ?? 'active'
                        ]);
                        $existingLayups->put($layupName, $layup);
                    }
                    $results['layups_created']++;
                    $manualLayup['id'] = null;
                    $manualLayup['status'] = 'new';
                }

                $existingLayers = [];
                if (isset($layup) && $layup) {
                    $existingLayers = $this->layerRepository->getAllByLayup($layup->id)->keyBy('layer_order');
                }

                foreach ($layupData['layers'] as $layerData) {
                    $order = $layerData['layer_order'];
                    $manualLayer = ['incoming' => $layerData, 'existing' => null, 'status' => 'new'];
                    
                    if (isset($existingLayers[$order])) {
                        $existing = $existingLayers[$order];
                        $manualLayer['existing'] = $existing->toArray();
                        
                        if ($existing->thickness != $layerData['thickness'] ||
                            $existing->width != $layerData['width'] ||
                            $existing->angle != $layerData['angle']) {
                                
                            $manualLayer['status'] = 'conflict';
                                
                            if ($conflictStrategy === 'manual') {
                                // Add to manual payload (wait for user)
                                $results['conflicts'][] = "Conflict detected in layer {$order} of layup {$layupName}";
                            } elseif ($conflictStrategy === 'skip') {
                                $results['layers_skipped']++;
                                $results['conflicts'][] = "Skipped layer {$order} in layup {$layupName}";
                            } else {
                                $this->layerRepository->update($existing->id, [
                                    'thickness' => $layerData['thickness'],
                                    'width' => $layerData['width'],
                                    'angle' => $layerData['angle'],
                                ]);
                                $results['layers_updated']++;
                                $results['conflicts'][] = "Overwrote layer {$order} in layup {$layupName}";
                            }
                        } else {
                            $manualLayer['status'] = 'unchanged';
                        }
                    } else {
                        if ($conflictStrategy !== 'manual') {
                            $this->layerRepository->create(array_merge($layerData, [
                                'layup_id' => $layup->id
                            ]));
                        }
                        $results['layers_created']++;
                    }
                    
                    $manualLayup['layers'][] = $manualLayer;
                }
                
                $results['manual_payload'][] = $manualLayup;
            }

            if ($conflictStrategy === 'manual' || $isDryRun) {
                DB::rollBack();
            }

            return $results;
        });
    }
}
