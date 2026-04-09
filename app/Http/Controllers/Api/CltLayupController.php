<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CltLayupService;
use Illuminate\Http\Request;

class CltLayupController extends Controller
{
    public function __construct(private CltLayupService $layupService)
    {}

    public function index(int $supplierId)
    {
        return response()->json($this->layupService->getAllBySupplier($supplierId));
    }

    public function store(Request $request, int $supplierId)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $validated['supplier_id'] = $supplierId;
        
        $layup = $this->layupService->createLayup($validated);
        return response()->json($layup, 201);
    }

    public function show(int $supplierId, int $id)
    {
        return response()->json($this->layupService->getById($id));
    }

    public function update(Request $request, int $supplierId, int $id)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $layup = $this->layupService->updateLayup($id, $validated);
        return response()->json($layup);
    }

    public function destroy(int $supplierId, int $id)
    {
        $this->layupService->deleteLayup($id);
        return response()->json(null, 204);
    }
}
