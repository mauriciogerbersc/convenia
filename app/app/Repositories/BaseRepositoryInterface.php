<?php 

namespace App\Repositories;

interface BaseRepositoryInterface
{
    public function create(array $data);

    public function all(array $columns = ['*']);

    public function updateById(array $data, int $id);
}