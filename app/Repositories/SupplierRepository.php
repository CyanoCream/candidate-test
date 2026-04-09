<?php

namespace App\Repositories;

use App\Interfaces\SupplierRepositoryInterface;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;

class SupplierRepository implements SupplierRepositoryInterface
{
    public function getAll(?string $search = null): Collection
    {
        $query = Supplier::withCount('layups');
        
        if ($search) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getById(int $id): ?Supplier
    {
        return Supplier::findOrFail($id);
    }

    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(int $id, array $data): Supplier
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update($data);
        return $supplier;
    }

    public function delete(int $id): bool
    {
        return Supplier::destroy($id);
    }

    public function getWithRelations(): Collection
    {
        return Supplier::with('layups.layers')->get();
    }
}
