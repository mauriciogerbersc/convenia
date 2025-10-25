<?php

namespace App\Jobs;

use App\Services\Employee\EmployeeService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEmployeesImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    private $employeeService;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public int $userId, public string $storagePath)
    {
        $this->onQueue('imports');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(EmployeeService $employeeService)
    {
        $full = storage_path('app/'.$this->storagePath);
        $rows = array_map('str_getcsv', file($full));
    
        $header = array_map('strtolower', array_map('trim', array_shift($rows)));
        $required = ['name','email','cpf','city','state'];

        if ($header !== $required) {
            return;
        }

        $inserted = 0; 
        $errors = [];
        $seenEmail = []; 
        $seenCpf = [];

        foreach ($rows as $i => $raw) {
            $line = $i + 2;
            $payload = array_combine($required, array_map('trim', $raw ?? []));

            $payload['document']   = preg_replace('/\D+/', '', $payload['cpf'] ?? '');
            
            $v = Validator::make($payload, [
                'name'  => ['required','string','max:255'],
                'email' => ['required','email','max:255',
                    Rule::unique('employees','email')->where(fn($q)=>$q->where('user_id',$this->userId))
                ],
                'document'   => ['required','digits:11',
                    Rule::unique('employees','document')->where(fn($q)=>$q->where('user_id',$this->userId))
                ],
                'city'  => ['required','string','max:255'],
                'state' => ['required','string','max:50'], 
            ]);

            if (isset($seenEmail[$payload['email']])) $v->after(fn($v)=>$v->errors()->add('email','Duplicado no arquivo.'));
            if (isset($seenCpf[$payload['cpf']]))     $v->after(fn($v)=>$v->errors()->add('cpf','Duplicado no arquivo.'));

            if ($v->fails()) {
                $errors[] = ['line'=>$line, 'errors'=>$v->errors()->all()];
                continue;
            }

            $data = $v->validated();
            $data['user_id'] = $this->userId;

            $employeeService->create($data);
            
            $seenEmail[$payload['email']] = true;
            $seenCpf[$payload['cpf']] = true;
            $inserted++;
        }
    }
}
