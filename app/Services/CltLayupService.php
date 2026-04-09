<?php

namespace App\Services;

use App\Interfaces\CltLayupRepositoryInterface;

class CltLayupService
{
    public function __construct(private CltLayupRepositoryInterface $layupRepository)
    {}

    public function getAllBySupplier(int $supplierId)
    {
        return $this->layupRepository->getAllBySupplier($supplierId);
    }

    public function getById(int $id)
    {
        return $this->layupRepository->getById($id);
    }

    public function createLayup(array $data)
    {
        return $this->layupRepository->create($data);
    }

    public function updateLayup(int $id, array $data)
    {
        return $this->layupRepository->update($id, $data);
    }

    public function deleteLayup(int $id)
    {
        return $this->layupRepository->delete($id);
    }
}
