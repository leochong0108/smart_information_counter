<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\QuestionLog;
use App\Models\Department;
use App\Models\Faq;
use App\Models\Intent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class statisticController extends Controller
{

    public function selectMost10(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        // 1. 定义基础 Query Builder
        // 我们不在这里直接 select，而是先建立 Query 实例
        $baseQuery = QuestionLog::where('status', true);

        // 2. 应用日期过滤器 (Refactored: 只写一次逻辑)
        $this->applyDateFilter($baseQuery, $filter, $startDate, $endDate);

        // 3. 克隆 Query 对象以用于不同的统计 (关键步骤)
        // 因为 Query Builder 是引用传递，我们需要 clone 来分别处理
        $queryFaq = clone $baseQuery;
        $queryIntent = clone $baseQuery;
        $queryDepartment = clone $baseQuery;

        // 4. 获取 Top 10 FAQs
        $top10Faqs = $queryFaq
            ->select('faqs.question', DB::raw('COUNT(question_logs.faq_id) as total'))
            ->join('faqs', 'question_logs.faq_id', '=', 'faqs.id')
            ->groupBy('faqs.question', 'faqs.id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // 5. 获取 Intent 统计
        $totalIntent = $queryIntent
            ->select('intents.intent_name', DB::raw('COUNT(question_logs.intent_id) as total'))
            ->join('intents', 'question_logs.intent_id', '=', 'intents.id')
            ->groupBy('intents.intent_name', 'intents.id')
            ->orderByDesc('total')
            ->get();

        // 6. 获取 Department 统计
        $totalDepartment = $queryDepartment
            ->select('departments.name', DB::raw('COUNT(question_logs.department_id) as total'))
            ->join('departments', 'question_logs.department_id', '=', 'departments.id')
            ->groupBy('departments.name', 'departments.id')
            ->orderByDesc('total')
            ->get();

        $data = [
            'Faq' => $top10Faqs,
            'Intent' => $totalIntent,
            'Department' => $totalDepartment
        ];

        return response()->json($data);
    }

    public function departmentTrend(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        // 1. 基础查询
        $query = QuestionLog::query()
            ->join('departments', 'question_logs.department_id', '=', 'departments.id')
            ->where('question_logs.status', true);

        // 2. 复用之前的日期过滤逻辑 (假设你已经提取了 applyDateFilter)
        $this->applyDateFilter($query, $filter, $startDate, $endDate);

        // 3. 决定时间格式 (Group By Format)
        // Daily -> 按小时看 (09:00, 10:00...)
        // Weekly/Monthly -> 按日期看 (2023-10-01...)
        // Yearly -> 按月份看 (Jan, Feb...)
        $dateFormat = '%Y-%m-%d'; // 默认按天
        $selectFormat = 'DATE(question_logs.created_at) as time_unit';

        if ($filter === 'daily') {
            $dateFormat = '%H:00'; // MySQL 格式，按小时
            // Laravel/MySQL 获取小时
            $selectFormat = "DATE_FORMAT(question_logs.created_at, '%H:00') as time_unit";
        } elseif ($filter === 'yearly') {
            $dateFormat = '%Y-%m'; // 按月
            $selectFormat = "DATE_FORMAT(question_logs.created_at, '%Y-%m') as time_unit";
        } else {
            // Weekly, Monthly, Custom Range 通常按天看
            $selectFormat = "DATE_FORMAT(question_logs.created_at, '%Y-%m-%d') as time_unit";
        }

        // 4. 执行查询
        $trends = $query->select(
                'departments.name as dept_name',
                DB::raw($selectFormat),
                DB::raw('COUNT(question_logs.id) as total')
            )
            ->groupBy('departments.name', 'time_unit')
            ->orderBy('time_unit', 'asc')
            ->get();

        // 5. 格式化数据给前端 Chart.js 使用
        // 我们需要把数据转换成: { labels: [日期...], datasets: [ {label: 'IT', data: [...]}, ...] }

        // 提取所有唯一的时间点作为 X 轴
        $labels = $trends->pluck('time_unit')->unique()->values()->all();

        // 按部门分组数据
        $datasets = [];
        $grouped = $trends->groupBy('dept_name');

        foreach ($grouped as $deptName => $records) {
            $dataPoints = [];
            foreach ($labels as $label) {
                // 查找该部门在该时间点是否有数据，没有填 0 (补零非常重要!)
                $record = $records->firstWhere('time_unit', $label);
                $dataPoints[] = $record ? $record->total : 0;
            }

            $datasets[] = [
                'label' => $deptName,
                'data' => $dataPoints,
                // 颜色前端生成，或者这里后端指定都可以
            ];
        }

        return response()->json([
            'labels' => $labels,
            'datasets' => $datasets
        ]);
    }

    /**
     * 提取出来的日期过滤辅助函数
     * 保持主逻辑清晰
     */
    private function applyDateFilter($query, $filter, $startDate, $endDate)
    {
        switch ($filter) {
            case 'daily':
                $query->whereDate('question_logs.created_at', Carbon::today());
                break;
            case 'weekly':
                // 使用 startOfWeek 可以根据配置决定周一还是周日开始
                $query->whereBetween('question_logs.created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]);
                break;
            case 'monthly':
                $query->whereMonth('question_logs.created_at', Carbon::now()->month)
                    ->whereYear('question_logs.created_at', Carbon::now()->year);
                break;
            case 'yearly':
                $query->whereYear('question_logs.created_at', Carbon::now()->year);
                break;
            case 'custom-range':
                if ($startDate && $endDate) {
                    $start = Carbon::parse($startDate)->startOfDay();
                    $end = Carbon::parse($endDate)->endOfDay();
                    $query->whereBetween('question_logs.created_at', [$start, $end]);
                }
                break;
            // default: all time, do nothing
        }
    }

    public function selectTop10ForChat()
    {
        $top10Faqs = QuestionLog::select(
            'faqs.question',
            DB::raw('COUNT(question_logs.faq_id) as total')
            )
            ->join('faqs', 'question_logs.faq_id', '=', 'faqs.id')
            ->where('question_logs.status', true)
            ->groupBy('faqs.question','faqs.id')
            ->orderByDesc('total')
            ->limit(7)
            ->get();
        return response()->json($top10Faqs);
    }

    public function totalIntents()
    {
        $intents = QuestionLog::select(
            'intents.intent_name',
            DB::raw('COUNT(question_logs.intent_id) as total')
            )
            ->join('intents', 'question_logs.intent_id', '=', 'intents.id')
            ->where('question_logs.status', true)
            ->groupBy('intents.intent_name','intents.id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
        return response()->json($intents);
    }

    public function getDashboardMetrics(Request $request)
    {
        // 1. 获取前端传来的参数
        $filter = $request->input('filter', 'all-time');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        // 2. 创建基础查询构建器
        $query = QuestionLog::query();

        // 3. 应用日期过滤逻辑 (这部分逻辑应该和你其他 API 保持一致)
        switch ($filter) {
            case 'daily':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'weekly':
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'monthly':
                $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
                break;
            case 'yearly':
                $query->whereYear('created_at', Carbon::now()->year);
                break;
            case 'custom-range':
                if ($startDate && $endDate) {
                    // 确保包含当天的结束时间 (23:59:59)
                    $start = Carbon::parse($startDate)->startOfDay();
                    $end = Carbon::parse($endDate)->endOfDay();
                    $query->whereBetween('created_at', [$start, $end]);
                }
                break;
            // 'all-time' 不需要做任何过滤
        }

        // 4. 使用 clone 来分别计算成功和失败，避免代码重复
        // 注意：必须 clone，否则第一个 count() 会执行查询，后续无法复用
        $totalFails = (clone $query)
            ->where('status', false)
            ->where(function($q) {
                // 只抓特定备注
                $q->where('remark', 'No matching knowledge found');
            })
            ->count();

        $totalSuccess = (clone $query)->where('status', true)->count();

        $totalQuestions = $totalFails + $totalSuccess;

        $metrics = [
            'totalFail' => $totalFails,
            'totalSuccess' => $totalSuccess,
            'totalQuestions' => $totalQuestions,
        ];

        return response()->json($metrics);
    }


}
