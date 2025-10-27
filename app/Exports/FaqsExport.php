<?php

namespace App\Exports;

use App\Models\Faq;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FaqsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function collection()
    {
        return Faq::select('id', 'question', 'answer', 'intent_id', 'department_id')->get();
    }

    public function headings(): array
    {
        return ['ID', 'Question', 'Answer', 'Intent ID', 'Department ID'];
    }
}
