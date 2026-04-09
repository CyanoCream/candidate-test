<?php

namespace App\Services;

use App\Interfaces\SupplierRepositoryInterface;

class SupplierService
{
    public function __construct(private SupplierRepositoryInterface $supplierRepository)
    {}

    // Basic delegate to repository. If business logic is needed, add here.
    public function getAllSuppliers(?string $search = null)
    {
        return $this->supplierRepository->getAll($search);
    }
    
    public function getSuppliersWithRelations()
    {
        return $this->supplierRepository->getWithRelations();
    }

    public function getSupplierById(int $id)
    {
        return $this->supplierRepository->getById($id);
    }

    public function createSupplier(array $data)
    {
        return $this->supplierRepository->create($data);
    }

    public function updateSupplier(int $id, array $data)
    {
        return $this->supplierRepository->update($id, $data);
    }

    public function deleteSupplier(int $id)
    {
        return $this->supplierRepository->delete($id);
    }
}
