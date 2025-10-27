<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;
use App\Services\ExcelService;
use App\Exports\FaqsExport;
use Maatwebsite\Excel\Facades\Excel;

class faqController extends Controller
{

    public function index()
    {
        $faqs = Faq::with(['intent', 'department'])->get();
        return response()->json($faqs);
    }

    public function show($id)
    {
        $faq = Faq::with(['intent', 'department'])->find($id);
        if (!$faq) {
            return response()->json(['message' => 'FAQ not found'], 404);
        }
        return response()->json($faq);
    }

    public function store(Request $request)
    {
        $request->merge([
            'intent_id' => $request->intent_id === 'null' || $request->intent_id === '' ? null : $request->intent_id,
            'department_id' => $request->department_id === 'null' || $request->department_id === '' ? null : $request->department_id,
        ]);

        $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'intent_id' => 'nullable|exists:intents,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $faq = Faq::create($request->all());
        return response()->json($faq, 201);
    }

    public function update(Request $request, $id)
    {
        $faq = Faq::find($id);
        if (!$faq) {
            return response()->json(['message' => 'FAQ not found'], 404);
        }

        $request->merge([
            'intent_id' => $request->intent_id === 'null' || $request->intent_id === '' ? null : $request->intent_id,
            'department_id' => $request->department_id === 'null' || $request->department_id === '' ? null : $request->department_id,
        ]);

        $request->validate([
            'question' => 'sometimes|required|string|max:255',
            'answer' => 'sometimes|required|string',
            'intent_id' => 'nullable|exists:intents,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $faq->update($request->all());
        return response()->json($faq);
    }

    public function destroy($id)
    {
        $faq = Faq::find($id);
        if (!$faq) {
            return response()->json(['message' => 'FAQ not found'], 404);
        }
        $faq->delete();
        return response()->json(['message' => 'FAQ deleted']);
    }

    protected $excelService;

    public function __construct(ExcelService $excelService)
    {
        $this->excelService = $excelService;
    }

    public function export()
    {
        return Excel::download(new FaqsExport, 'faqs.xlsx');
    }

    // ✅ Import FAQs from Excel
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $rows = $this->excelService->import($request->file('file'));

        foreach ($rows as $row) {
            Faq::updateOrCreate(
                ['question' => $row['question']], // avoid duplicates
                [
                    'answer' => $row['answer'] ?? null,
                    'intent_id' => $row['intent_id'] ?? null,
                    'department_id' => $row['department_id'] ?? null,
                ]
            );
        }

        return response()->json(['message' => 'FAQs imported successfully']);
    }
}
