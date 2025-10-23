<?php 

namespace App\Repositories\Employee;

use App\Models\Employee;
use App\Repositories\BaseRepository;
use App\Repositories\BaseRepositoryInterface;

class EmployeeRepository extends BaseRepository implements BaseRepositoryInterface
{
    public function __construct(Employee $model) {
        parent::__construct($model);
    }

    public function listByUser(array $columns = ['*']) 
    {
        return $this->model->where($columns)->get();
    }

    public function create(array $params): Employee
    {
        return $this->model->create($params);
    }
}