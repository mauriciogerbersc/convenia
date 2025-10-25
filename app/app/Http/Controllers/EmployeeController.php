<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportEmployeesRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Jobs\ProcessEmployeesImport;
use App\Models\Employee;
use App\Services\Employee\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    /**
     * @param EmployeeService $employee
     */
    public function __construct(private EmployeeService $employee)
    {
        $this->employee = $employee;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $employees = $this->employee->listByUser($request->user()->id);

        return response()->json($employees);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEmployeeRequest $request)
    {
        $employeeData = $request->validated();
        $employeCreated = $this->createEmployee($employeeData, $request->user()->id);

        return response()->json($employeCreated, 200);
    }

    /**
     * @param array $employeeData
     * @param integer $userId
     * @return void
     */
    private function createEmployee(array $employeeData, int $userId)
    {
        $employeeData['user_id'] = $userId;
        $employee = $this->employee->create($employeeData);

        return $employee;
    }

    /**
     * Update the specified resource in storage
     *
     * @param UpdateEmployeeRequest $request
     * @param Employee $employee
     * @return void
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->isNotAuthorized($request, $employee);
        $this->employee->update($request->validated(), $employee->id);

        return response()->json([], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param Employee $employee
     * @return void
     */
    public function destroy(Request $request, Employee $employee)
    {
        $this->isNotAuthorized($request, $employee);
        $this->employee->delete($employee->id);

        return response()->json([], 204);
    }

    /**
     * @param ImportEmployeesRequest $request
     * @return void
     */
    public function import(ImportEmployeesRequest $request)
    {
        $filename = Str::uuid().'.csv';
        $path = $request->file('file')->storeAs("imports/{$request->user()->id}", $filename);

        ProcessEmployeesImport::dispatch($request->user()->id, $path);

        return response()->json([
            'status' => 'queued',
            'file'   => $filename,
        ], 202);
    }

    /**
     * @param Request $request
     * @param Employee $employee
     * @return boolean
     */
    private function isNotAuthorized(Request $request, Employee $employee)
    {
        abort_if($employee->user_id !== $request->user()->id, 403, 'Forbidden');
    }
}
