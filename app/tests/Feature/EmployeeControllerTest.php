<?php

namespace Tests\Feature;

use App\Jobs\ProcessEmployeesImport;
use App\Models\Employee;
use App\Models\User;
use App\Services\Employee\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        $this->actingAs($user, 'api');
        return ['Accept' => 'application/json'];
    }

    /** @test */
    public function index_returns_list_for_authenticated_user()
    {
        $user = User::factory()->create();

        $this->mock(EmployeeService::class, function ($mock) use ($user) {
            $mock->shouldReceive('listByUser')
                ->once()
                ->with($user->id)
                ->andReturn([
                    ['id' => 1, 'name' => 'Ana', 'email' => 'ana@x.com', 'cpf' => '11122233344', 'city' => 'Floripa', 'state' => 'Santa Catarina', 'user_id' => $user->id],
                    ['id' => 2, 'name' => 'Beto', 'email' => 'beto@x.com', 'cpf' => '99988877766', 'city' => 'Floripa', 'state' => 'Santa Catarina', 'user_id' => $user->id],
                ]);
        });

        $res = $this->getJson('/api/employees', $this->authHeaders($user));
        $res->assertOk()
            ->assertJsonFragment(['email' => 'ana@x.com'])
            ->assertJsonFragment(['email' => 'beto@x.com']);
    }

    /** @test */
    public function store_creates_employee_with_logged_user_id()
    {
        $user = User::factory()->create();

        $payload = [
            'name'     => 'Ana',
            'email'    => 'ana@x.com',
            'document' => '12345678901',
            'city'     => 'Florianópolis',
            'state'    => 'SC',
        ];

        $this->mock(EmployeeService::class, function ($mock) use ($user) {
            $mock->shouldReceive('create')
                ->once()
                ->with(\Mockery::on(function ($data) use ($user) {
                    return ($data['user_id'] ?? null) === $user->id
                        && $data['name'] === 'Ana';
                }))
                ->andReturn([
                    'id'       => 10,
                    'name'     => 'Ana',
                    'email'    => 'ana@x.com',
                    'document' => '12345678901',
                    'city'     => 'Florianópolis',
                    'state'    => 'SC',
                    'user_id'  => $user->id,
                ]);
        });

        $res = $this->postJson('/api/employees', $payload, $this->authHeaders($user));
        $res->assertOk()
            ->assertJsonFragment(['id' => 10, 'email' => 'ana@x.com']);
    }

    /** @test */
    public function update_returns_403_when_not_owner()
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $emp = Employee::factory()->for($owner)->create();

        $this->mock(EmployeeService::class, function ($mock) {
            $mock->shouldReceive('update')->never();
        });

        $res = $this->put("/api/employees/{$emp->id}", [
            'name' => 'Hack'
        ], $this->authHeaders($intruder));

        $res->assertStatus(403);
    }

    /** @test */
    public function update_updates_when_owner()
    {
        $owner = User::factory()->create();
        $emp = Employee::factory()->for($owner)->create();

        $this->mock(EmployeeService::class, function ($mock) use ($emp) {
            $mock->shouldReceive('update')
                ->once()
                ->with(\Mockery::subset(['name' => 'Novo Nome']), $emp->id)
                ->andReturnTrue();
        });

        $res = $this->put("/api/employees/{$emp->id}", [
            'name' => 'Novo Nome'
        ], $this->authHeaders($owner));

        $res->assertStatus(201);
    }

    /** @test */
    public function destroy_returns_403_when_not_owner()
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $emp = Employee::factory()->for($owner)->create();

        $this->mock(EmployeeService::class, function ($mock) {
            $mock->shouldReceive('delete')->never();
        });

        $res = $this->deleteJson("/api/employees/{$emp->id}", [], $this->authHeaders($intruder));
        $res->assertStatus(403);
    }

    /** @test */
    public function destroy_deletes_when_owner()
    {
        $owner = User::factory()->create();
        $emp = Employee::factory()->for($owner)->create();

        $this->mock(EmployeeService::class, function ($mock) use ($emp) {
            $mock->shouldReceive('delete')
                ->once()
                ->with($emp->id)
                ->andReturnTrue();
        });

        $res = $this->deleteJson("/api/employees/{$emp->id}", [], $this->authHeaders($owner));
        $res->assertNoContent(); // 204
    }

    /** @test */
    public function import_queues_job_and_stores_file()
    {
        $user = User::factory()->create();

        Storage::fake('local');
        Queue::fake();

        $csv  = "name,email,cpf,city,state\nAna,ana@x.com,12345678901,Floripa,SC\n";
        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $res = $this->post('/api/employees/importEmployees', ['file' => $file], $this->authHeaders($user));

        $res->assertStatus(202)
            ->assertJsonStructure(['status','file']);

        $filename   = $res->json('file');
        $storedPath = "imports/{$user->id}/{$filename}";

        Storage::disk('local')->assertExists($storedPath);

        Queue::assertPushed(ProcessEmployeesImport::class, function ($job) use ($user, $storedPath) {
            return $job->userId === $user->id
                && $job->storagePath === $storedPath;
        });
    }
}
