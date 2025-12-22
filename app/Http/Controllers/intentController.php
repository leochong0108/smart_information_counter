<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Intent;
use App\Http\Requests\StoreIntentRequest; // 引入 Request

class IntentController extends Controller
{
    public function index()
    {
        // 包含关联关系，方便前端展示
        $intents = Intent::with(['faqs', 'department'])->get();
        return response()->json($intents);
    }

    public function show($id)
    {
        $intent = Intent::with('faqs')->find($id);
        if (!$intent) {
            return response()->json(['message' => 'Intent not found'], 404);
        }
        return response()->json($intent);
    }

    // 使用 Request 自动验证
    public function store(StoreIntentRequest $request)
    {
        // $request->validated() 返回的是处理干净的数据（department_id 已经是 null 而不是 "null"）
        $intent = Intent::create($request->validated());
        return response()->json($intent, 201);
    }

    public function update(StoreIntentRequest $request, $id)
    {
        $intent = Intent::find($id);
        if (!$intent) {
            return response()->json(['message' => 'Intent not found'], 404);
        }

        $intent->update($request->validated());
        return response()->json($intent);
    }

    public function destroy($id)
    {
        $intent = Intent::find($id);
        if (!$intent) {
            return response()->json(['message' => 'Intent not found'], 404);
        }

        $intent->delete();
        return response()->json(['message' => 'Intent deleted']);
    }
}
