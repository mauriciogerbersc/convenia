<?php 

namespace App\Services\Employee;

use App\Repositories\Employee\EmployeeRepository;
use App\Services\BaseService;
use App\Services\BaseServiceInterface;

class EmployeeService extends BaseService implements BaseServiceInterface
{
    protected $employeeRepository;
    
    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function listByUser(int $userId): array
    {
        return $this->employeeRepository->listByUser(['user_id' => $userId])->toArray();
    }

    public function create(array $params): array
    {
        return $this->employeeRepository->create($params)->toArray();
    }

    public function update(array $params, int $employeeId): int
    {
        return $this->employeeRepository->updateById($params, $employeeId);
    }
}