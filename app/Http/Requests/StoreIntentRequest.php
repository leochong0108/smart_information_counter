<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'intent_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
        ];
    }


    protected function prepareForValidation()
    {
        $this->merge([
            'department_id' => ($this->department_id === 'null' || $this->department_id === '') ? null : $this->department_id,
        ]);
    }
}
