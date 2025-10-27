<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class ExcelService
{
    /**
     * Export model data to Excel
     */
    public function export(string $fileName, Collection $data, array $headers)
    {
        $exportData = $data->map(function ($item) use ($headers) {
            $row = [];
            foreach ($headers as $key => $column) {
                // push values in order, not key-value pairs
                $row[] = $item[$key] ?? null;
            }
            return $row;
        });

        $headings = array_values($headers);

        // ✅ Properly define export class
        $export = new class($exportData, $headings) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings {

            protected $data;
            protected $headings;

            public function __construct($data, $headings)
            {
                $this->data = $data;
                $this->headings = $headings;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return $this->headings;
            }
        };

        return Excel::download($export, $fileName);
    }

    /**
     * Import Excel file and return as array
     */
    public function import($file)
    {
        // this returns a Collection of rows with headings as keys
        return Excel::toCollection(null, $file)->first();
    }
}
