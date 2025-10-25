<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = Employee::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'name'    => $this->faker->name(),
            'email'   => $this->faker->unique()->safeEmail(),
            'document'     => $this->faker->numerify('###########'), 
            'city'    => $this->faker->city(),
            'state'   => 'Santa Catarina', 
        ];
    }
}
