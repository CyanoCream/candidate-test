<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\CltLayupService;
use App\Services\CltLayerService;
use Illuminate\Http\Request;

class WebLayupController extends Controller
{
    public function __construct(
        private CltLayupService $layupService,
        private CltLayerService $layerService
    ) {}

    public function show(int $id)
    {
        $layup = $this->layupService->getById($id);
        $layers = $this->layerService->getAllByLayup($id);
        
        return view('layups.show', compact('layup', 'layers'));
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'layers' => 'present|array',
            'layers.*.id' => 'nullable|integer',
            'layers.*.layer_order' => 'required|integer',
            'layers.*.thickness' => 'required|numeric',
            'layers.*.width' => 'required|numeric',
            'layers.*.angle' => 'required|numeric',
            'layers.*.grade' => 'nullable|string|max:50',
        ]);

        $layup = $this->layupService->getById($id);
        $incomingLayers = collect($request->input('layers'));

        // Retrieve existing layers to handle deletions
        $existingLayers = $this->layerService->getAllByLayup($id);

        $incomingIds = $incomingLayers->pluck('id')->filter()->toArray();

        // Delete layers that are no longer in the request
        foreach ($existingLayers as $existing) {
            if (!in_array($existing->id, $incomingIds)) {
                $this->layerService->deleteLayer($existing->id);
            }
        }

        // Create or update layers
        foreach ($incomingLayers as $index => $layerData) {
            // enforce correct order according to the UI positioning
            $layerData['layer_order'] = $index + 1;
            
            if (isset($layerData['id']) && $layerData['id']) {
                $this->layerService->updateLayer($layerData['id'], $layerData);
            } else {
                $layerData['layup_id'] = $layup->id;
                $this->layerService->createLayer($layerData);
            }
        }

        return response()->json(['message' => 'Layup saved successfully']);
    }
}
