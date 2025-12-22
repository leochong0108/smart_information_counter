<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Http\Requests\StoreFaqRequest; // 引入刚才创建的 Request
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

    // 使用 StoreFaqRequest 自动处理验证和数据清理
    public function store(StoreFaqRequest $request)
    {
        // $request->validated() 只会返回规则里验证过的数据
        $faq = Faq::create($request->validated());
        return response()->json($faq, 201);
    }

    // 更新逻辑其实可以用同一个 Request，或者新建 UpdateFaqRequest
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
