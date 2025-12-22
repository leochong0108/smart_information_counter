<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 允许所有登录用户操作
    }

    public function rules(): array
    {
        return [
            'intent_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
        ];
    }

    /**
     * 在验证之前清理数据
     * 解决前端传 "null" 字符串的问题
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'department_id' => ($this->department_id === 'null' || $this->department_id === '') ? null : $this->department_id,
        ]);
    }
}
