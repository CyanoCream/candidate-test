<?php

namespace App\Services;

use App\Interfaces\CltLayerRepositoryInterface;

class CltLayerService
{
    public function __construct(private CltLayerRepositoryInterface $layerRepository)
    {}

    public function getAllByLayup(int $layupId)
    {
        return $this->layerRepository->getAllByLayup($layupId);
    }

    public function getById(int $id)
    {
        return $this->layerRepository->getById($id);
    }

    public function createLayer(array $data)
    {
        return $this->layerRepository->create($data);
    }

    public function updateLayer(int $id, array $data)
    {
        return $this->layerRepository->update($id, $data);
    }

    public function deleteLayer(int $id)
    {
        return $this->layerRepository->delete($id);
    }
}
