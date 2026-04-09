<?php

namespace App\Repositories;

use App\Interfaces\CltLayupRepositoryInterface;
use App\Models\CltLayup;
use Illuminate\Database\Eloquent\Collection;

class CltLayupRepository implements CltLayupRepositoryInterface
{
    public function getAllBySupplier(int $supplierId): Collection
    {
        return CltLayup::where('supplier_id', $supplierId)->get();
    }

    public function getById(int $id): ?CltLayup
    {
        return CltLayup::findOrFail($id);
    }

    public function create(array $data): CltLayup
    {
        return CltLayup::create($data);
    }

    public function update(int $id, array $data): CltLayup
    {
        $layup = CltLayup::findOrFail($id);
        $layup->update($data);
        return $layup;
    }

    public function delete(int $id): bool
    {
        return CltLayup::destroy($id);
    }
}
