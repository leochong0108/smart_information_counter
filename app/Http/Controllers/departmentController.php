<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Http\Requests\StoreDepartmentRequest; // 引入验证类
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        // 保持原来的逻辑，或者在这里加上分页也可以
        return response()->json(Department::all());
    }

    public function show($id)
    {
        $department = Department::find($id);
        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }
        return response()->json($department);
    }

    // 复用 StoreDepartmentRequest 处理新建
    public function store(StoreDepartmentRequest $request)
    {
        $department = Department::create($request->validated());
        return response()->json($department, 201);
    }

    // 复用 StoreDepartmentRequest 处理更新
    public function update(StoreDepartmentRequest $request, $id)
    {
        $department = Department::find($id);
        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        $department->update($request->validated());
        return response()->json($department);
    }

    public function destroy($id)
    {
        $department = Department::find($id);
        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        // 检查是否有依赖数据 (可选优化: 防止删除有 Intent/FAQ 的部门)
        // if ($department->faqs()->exists() || $department->intents()->exists()) {
        //     return response()->json(['message' => 'Cannot delete: This department has associated FAQs or Intents.'], 400);
        // }

        $department->delete();
        return response()->json(['message' => 'Department deleted']);
    }
}
