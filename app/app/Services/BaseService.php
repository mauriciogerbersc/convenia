<?php

namespace App\Services;

use App\Services\BaseServiceInterface;
use App\Repositories\BaseRepositoryInterface;

class BaseService implements BaseServiceInterface
{
    protected BaseRepositoryInterface $repository;

    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
    
    public function create(array $data)
    {
        return $this->repository->create($data);
    }
}
