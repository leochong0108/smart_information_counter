<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;

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
}
