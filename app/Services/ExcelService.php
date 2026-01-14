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

    public function import($file)
    {

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
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'type' => 'required|string|in:faq,department,intent', // 限制只能传这三种类型
        ]);

        $rows = $this->import($request->file('file'));
        $type = $request->input('type');

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

    private function importDepartments($rows)
    {
        foreach ($rows as $row) {
            if (empty($row['name'])) continue;

            Department::updateOrCreate(
                ['name' => $row['name']],
                [
                    'description'  => $row['description'] ?? null,
                    'location'     => $row['location'] ?? null,
                    'contact_info' => $row['contact_info'] ?? null,
                ]
            );
        }
    }


    private function importIntents($rows)
    {
        $departmentsMap = array_change_key_case(
            Department::pluck('id', 'name')->toArray(),
            CASE_LOWER
        );

        foreach ($rows as $row) {
            if (empty($row['intent_name'])) continue;

            $excelDeptName = isset($row['department']) ? strtolower(trim($row['department'])) : null;
            $departmentId = $excelDeptName ? ($departmentsMap[$excelDeptName] ?? null) : null;

            Intent::updateOrCreate(
                ['intent_name' => $row['intent_name']],
                [
                    'department_id' => $departmentId,
                ]
            );
        }
    }


    private function importFaqs($rows)
    {

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
