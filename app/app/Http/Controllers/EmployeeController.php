<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Services\Employee\EmployeeService;
use Illuminate\Http\Request;

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
        $employeeData['user_id'] = $request->user()->id;
        $employee = $this->employee->create($employeeData);

        return response()->json($employee, 200);
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
        $this->employee->delete($employee->id);

        return response()->json([], 204);
    }
}
