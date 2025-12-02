<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\Department;
use App\Models\Intent;


class ExcelService
{
    /**
     * Import Excel file and return as array
     */
    public function import($file)
    {
        // 使用匿名类，强制让 Excel 库识别表头
        // 这样它会自动把表头转为小写 (例如 "Question" -> "question")
        $collection = Excel::toCollection(new class implements ToCollection, WithHeadingRow {
            public function collection(Collection $rows)
            {
                return $rows;
            }
        }, $file);

        return $collection->first();
    }

    public function importExcel(Request $request)
    {
        // 1. 验证文件和类型
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'type' => 'required|string|in:faq,department,intent', // 限制只能传这三种类型
        ]);

        // 2. 读取 Excel 数据 (通用步骤)
        $rows = $this->import($request->file('file'));
        $type = $request->input('type');

        // 3. 根据类型分发给不同的处理逻辑
        switch ($type) {
            case 'department':
                $this->importDepartments($rows);
                break;
            case 'intent':
                $this->importIntents($rows);
                break;
            case 'faq':
                $this->importFaqs($rows);
                break;
        }

        return response()->json(['message' => ucfirst($type) . 's imported successfully']);
    }

    // --- 私有处理函数 ---

    /**
     * 处理 Department 导入
     * 字段: name, description, location, contact_info
     */
    private function importDepartments($rows)
    {
        foreach ($rows as $row) {
            // 基础检查
            if (empty($row['name'])) continue;

            Department::updateOrCreate(
                ['name' => $row['name']], // 假设 name 是唯一的
                [
                    'description'  => $row['description'] ?? null,
                    'location'     => $row['location'] ?? null,
                    // 注意：Excel 表头如果是 "Contact Info"，这里键名通常是 contact_info
                    'contact_info' => $row['contact_info'] ?? null,
                ]
            );
        }
    }

    /**
     * 处理 Intent 导入
     * 字段: intent_name, description, department_id (Excel里是 department 名字)
     */
    private function importIntents($rows)
    {
        // 1. 预加载 Department 对照表 (名字 => ID)
        $departmentsMap = array_change_key_case(
            Department::pluck('id', 'name')->toArray(),
            CASE_LOWER
        );

        foreach ($rows as $row) {
            if (empty($row['intent_name'])) continue;

            // 2. 查找 Department ID
            $excelDeptName = isset($row['department']) ? strtolower(trim($row['department'])) : null;
            $departmentId = $excelDeptName ? ($departmentsMap[$excelDeptName] ?? null) : null;

            Intent::updateOrCreate(
                ['intent_name' => $row['intent_name']], // 唯一键
                [
                    'department_id' => $departmentId,
                ]
            );
        }
    }

    /**
     * 处理 FAQ 导入 (你原本的逻辑)
     * 字段: question, answer, intent_id, department_id
     */
    private function importFaqs($rows)
    {
        // 1. 预加载对照表
        $intentsMap = array_change_key_case(
            Intent::pluck('id', 'intent_name')->toArray(),
            CASE_LOWER
        );
        $departmentsMap = array_change_key_case(
            Department::pluck('id', 'name')->toArray(),
            CASE_LOWER
        );

        foreach ($rows as $row) {
            if (empty($row['question'])) continue;

            // 2. 查找 ID
            $excelIntentName = isset($row['intent']) ? strtolower(trim($row['intent'])) : null;
            $excelDeptName = isset($row['department']) ? strtolower(trim($row['department'])) : null;

            $intentId = $excelIntentName ? ($intentsMap[$excelIntentName] ?? null) : null;
            $departmentId = $excelDeptName ? ($departmentsMap[$excelDeptName] ?? null) : null;

            Faq::updateOrCreate(
                ['question' => $row['question']],
                [
                    'answer'        => $row['answer'] ?? null,
                    'intent_id'     => $intentId,
                    'department_id' => $departmentId,
                ]
            );
        }
    }
}
