<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
            'name'  => ['sometimes','required','string','max:255'],
            'email' => ['sometimes','required','email','max:255',
                Rule::unique('employees')->ignore($this->route('employee')->id)->where(fn($q)=>$q->where('user_id',$this->user()->id))
            ],
            'document'   => ['sometimes','required','digits:11',
                Rule::unique('employees')->ignore($this->route('employee')->id)->where(fn($q)=>$q->where('user_id',$this->user()->id))
            ],
            'city'  => ['sometimes','required','string','max:255'],
            'state' => ['sometimes','required','string','size:2'],
        ];
    }
}
