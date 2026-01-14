<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Http\Requests\StoreFaqRequest;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        return response()->json(Faq::with(['intent', 'department'])->get());
    }

    public function show($id)
    {
        $faq = Faq::with(['intent', 'department'])->find($id);
        if (!$faq) {
            return response()->json(['message' => 'FAQ not found'], 404);
        }
        return response()->json($faq);
    }

    public function store(StoreFaqRequest $request)
    {
        $faq = Faq::create($request->validated());
        return response()->json($faq, 201);
    }

    public function update(StoreFaqRequest $request, $id)
    {
        $faq = Faq::find($id);
        if (!$faq) {
            return response()->json(['message' => 'FAQ not found'], 404);
        }

        $faq->update($request->validated());
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
