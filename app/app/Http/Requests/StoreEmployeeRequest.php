<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
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
            'name' => 'required|string',
            'email' => 'required|email|unique:employees',
            'document' => 'required|digits:11|unique:employees',
            'city' => 'required|string',
            'state' => 'required|string|max:50',
        ];
    }

    protected function prepareForValidation(): void
    {
        $document = preg_replace('/\D+/', '', (string) $this->input('document'));

        $this->merge([
            'document' => $document
        ]);
    }
}
