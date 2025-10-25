<?php 

namespace App\Services\Employee;

use App\Repositories\Employee\EmployeeRepository;
use App\Services\BaseService;
use App\Services\BaseServiceInterface;
use Illuminate\Support\Facades\Cache;

class EmployeeService extends BaseService implements BaseServiceInterface
{
    protected $employeeRepository;
    
    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function listByUser(int $userId): array
    {
        $key = "employees:user:{$userId}";
        
        $employees = Cache::remember($key, now()->addMinutes(5), function() use ($userId) {
            return $this->employeeRepository->listByUser(['user_id' => $userId])->toArray();
        });

        return $employees;
    }

    public function create(array $params): array
    {
        return $this->employeeRepository->create($params)->toArray();
    }

    public function update(array $params, int $employeeId): int
    {
        return $this->employeeRepository->updateById($params, $employeeId);
    }

    public function delete(int $employeeId)
    {
        return $this->employeeRepository->delete($employeeId);
    }
}