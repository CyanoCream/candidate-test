<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupplierService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(private SupplierService $supplierService)
    {}

    public function index()
    {
        return response()->json($this->supplierService->getAllSuppliers());
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $supplier = $this->supplierService->createSupplier($validated);
        return response()->json($supplier, 201);
    }

    public function show(int $id)
    {
        return response()->json($this->supplierService->getSupplierById($id));
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $supplier = $this->supplierService->updateSupplier($id, $validated);
        return response()->json($supplier);
    }

    public function destroy(int $id)
    {
        $this->supplierService->deleteSupplier($id);
        return response()->json(null, 204);
    }
}
