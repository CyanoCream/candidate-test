<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CltLayerService;
use Illuminate\Http\Request;

class CltLayerController extends Controller
{
    public function __construct(private CltLayerService $layerService)
    {}

    public function index(int $layupId)
    {
        return response()->json($this->layerService->getAllByLayup($layupId));
    }

    public function store(Request $request, int $layupId)
    {
        $validated = $request->validate([
            'layer_order' => 'required|integer',
            'thickness' => 'required|numeric',
            'width' => 'required|numeric',
            'angle' => 'required|numeric',
        ]);
        $validated['layup_id'] = $layupId;
        
        $layer = $this->layerService->createLayer($validated);
        return response()->json($layer, 201);
    }

    public function show(int $layupId, int $id)
    {
        return response()->json($this->layerService->getById($id));
    }

    public function update(Request $request, int $layupId, int $id)
    {
        $validated = $request->validate([
            'layer_order' => 'required|integer',
            'thickness' => 'required|numeric',
            'width' => 'required|numeric',
            'angle' => 'required|numeric',
        ]);
        $layer = $this->layerService->updateLayer($id, $validated);
        return response()->json($layer);
    }

    public function destroy(int $layupId, int $id)
    {
        $this->layerService->deleteLayer($id);
        return response()->json(null, 204);
    }
}
