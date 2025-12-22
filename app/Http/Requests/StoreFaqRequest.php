<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 允许所有登录用户
    }

    public function rules(): array
    {
        return [
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'intent_id' => 'nullable', // 允许空
            'department_id' => 'nullable',
        ];
    }

    // 数据预处理：处理前端传来的 "null" 字符串问题
    protected function prepareForValidation()
    {
        $this->merge([
            'intent_id' => ($this->intent_id === 'null' || $this->intent_id === '') ? null : $this->intent_id,
            'department_id' => ($this->department_id === 'null' || $this->department_id === '') ? null : $this->department_id,
        ]);
    }
}
