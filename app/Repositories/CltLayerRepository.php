<?php

namespace App\Repositories;

use App\Interfaces\CltLayerRepositoryInterface;
use App\Models\CltLayer;
use Illuminate\Database\Eloquent\Collection;

class CltLayerRepository implements CltLayerRepositoryInterface
{
    public function getAllByLayup(int $layupId): Collection
    {
        return CltLayer::where('layup_id', $layupId)->orderBy('layer_order')->get();
    }

    public function getById(int $id): ?CltLayer
    {
        return CltLayer::findOrFail($id);
    }

    public function create(array $data): CltLayer
    {
        return CltLayer::create($data);
    }

    public function update(int $id, array $data): CltLayer
    {
        $layer = CltLayer::findOrFail($id);
        $layer->update($data);
        return $layer;
    }

    public function delete(int $id): bool
    {
        return CltLayer::destroy($id);
    }
}
