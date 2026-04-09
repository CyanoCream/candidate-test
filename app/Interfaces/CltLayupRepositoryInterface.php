<?php

namespace App\Interfaces;

interface CltLayupRepositoryInterface
{
    public function getAllBySupplier(int $supplierId);
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
