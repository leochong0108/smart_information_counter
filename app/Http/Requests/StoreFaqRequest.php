<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'intent_id' => 'nullable',
            'department_id' => 'nullable',
        ];
    }


    protected function prepareForValidation()
    {
        $this->merge([
            'intent_id' => ($this->intent_id === 'null' || $this->intent_id === '') ? null : $this->intent_id,
            'department_id' => ($this->department_id === 'null' || $this->department_id === '') ? null : $this->department_id,
        ]);
    }
}
