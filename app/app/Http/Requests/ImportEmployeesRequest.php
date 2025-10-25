<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ImportEmployeesRequest extends FormRequest
{
    protected array $validRows = [];
    protected array $rowErrors = [];
    protected array $header = [];
    protected array $requiredHeader = ['name', 'email', 'cpf', 'city', 'state'];

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->hasFile('file')) {
                return;
            }

            $path = $this->file('file')->getRealPath();
            $rows = array_map('str_getcsv', file($path));
            if (empty($rows)) {
                $validator->errors()->add('file', 'CSV vazio.');
                return;
            }

            $this->header = array_map(fn($v) => strtolower(trim((string)$v)), array_shift($rows));
            if ($this->header !== $this->requiredHeader) {
                $validator->errors()->add('file', 'CSV header must be: name,email,cpf,city,state');
                return;
            }

            $userId = $this->user()->id;

            $seenEmail = [];
            $seenCpf   = [];

            foreach ($rows as $i => $raw) {
                $line = $i + 2;
                $payload = array_combine($this->requiredHeader, array_map(fn($v) => trim((string)$v), $raw ?? []));

                $payload['document'] = preg_replace('/\D+/', '', $payload['cpf'] ?? '');
                $payload['state'] = $payload['state'] ?? '';

                $rowValidator = Validator::make($payload, [
                    'name'  => ['required', 'string', 'max:255'],
                    'email' => [
                        'required',
                        'email',
                        'max:255',
                        Rule::unique('employees', 'email')
                            ->where(fn($q) => $q->where('user_id', $userId))
                    ],
                    'document'   => [
                        'required',
                        'digits:11',
                        Rule::unique('employees', 'document')
                            ->where(fn($q) => $q->where('user_id', $userId))
                    ],
                    'city'  => ['required', 'string', 'max:255'],
                    'state' => ['required', 'string', 'max:50'],
                ]);

                if (isset($seenEmail[$payload['email']])) {
                    $rowValidator->after(function ($v) {
                        $v->errors()->add('email', 'Duplicado no arquivo.');
                    });
                }
                if (isset($seenCpf[$payload['document']])) {
                    $rowValidator->after(function ($v) {
                        $v->errors()->add('document', 'Duplicado no arquivo.');
                    });
                }

                if ($rowValidator->fails()) {
                    $this->rowErrors[] = [
                        'line'   => $line,
                        'errors' => $rowValidator->errors()->all(),
                    ];
                    continue;
                }

                $seenEmail[$payload['email']] = true;
                $seenCpf[$payload['document']] = true;
                $this->validRows[] = $rowValidator->validated();
            }

            if (count($this->validRows) === 0 && count($this->rowErrors) > 0) {
                $lines = array_map(function ($err) {
                    return 'Linha ' . $err['line'] . ': ' . implode('; ', $err['errors']);
                }, $this->rowErrors);

                throw ValidationException::withMessages([
                    'file' => $lines,
                ]);
            }
        });
    }

    public function validRows(): array
    {
        return $this->validRows;
    }

    public function rowErrors(): array
    {
        return $this->rowErrors;
    }
}
