<?php

namespace App\Interfaces;

interface CltLayerRepositoryInterface
{
    public function getAllByLayup(int $layupId);
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
