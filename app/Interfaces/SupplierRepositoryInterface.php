<?php

namespace App\Interfaces;

interface SupplierRepositoryInterface
{
    public function getAll(?string $search = null);
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function getWithRelations();
}
