<?php

namespace App\Services;

use App\Models\QuestionLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    public function getTopStats($filter, $startDate, $endDate)
    {
        $baseQuery = QuestionLog::where('status', true);
        $this->applyDateFilter($baseQuery, $filter, $startDate, $endDate);

        $queryFaq = clone $baseQuery;
        $queryIntent = clone $baseQuery;
        $queryDepartment = clone $baseQuery;

        return [
            'Faq' => $queryFaq
                ->select('faqs.question', DB::raw('COUNT(question_logs.faq_id) as total'))
                ->join('faqs', 'question_logs.faq_id', '=', 'faqs.id')
                ->groupBy('faqs.question', 'faqs.id')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'Intent' => $queryIntent
                ->select('intents.intent_name', DB::raw('COUNT(question_logs.intent_id) as total'))
                ->join('intents', 'question_logs.intent_id', '=', 'intents.id')
                ->groupBy('intents.intent_name', 'intents.id')
                ->orderByDesc('total')
                ->get(),
            'Department' => $queryDepartment
                ->select('departments.name', DB::raw('COUNT(question_logs.department_id) as total'))
                ->join('departments', 'question_logs.department_id', '=', 'departments.id')
                ->groupBy('departments.name', 'departments.id')
                ->orderByDesc('total')
                ->get()
        ];
    }


    public function getDepartmentTrend($filter, $startDate, $endDate)
    {
        $query = QuestionLog::query()
            ->join('departments', 'question_logs.department_id', '=', 'departments.id')
            ->where('question_logs.status', true);

        $this->applyDateFilter($query, $filter, $startDate, $endDate);

        $selectFormat = "DATE_FORMAT(question_logs.created_at, '%Y-%m-%d') as time_unit";
        if ($filter === 'daily') {
            $selectFormat = "DATE_FORMAT(question_logs.created_at, '%H:00') as time_unit";
        } elseif ($filter === 'yearly') {
            $selectFormat = "DATE_FORMAT(question_logs.created_at, '%Y-%m') as time_unit";
        }

        $trends = $query->select(
                'departments.name as dept_name',
                DB::raw($selectFormat),
                DB::raw('COUNT(question_logs.id) as total')
            )
            ->groupBy('departments.name', 'time_unit')
            ->orderBy('time_unit', 'asc')
            ->get();

        $labels = $trends->pluck('time_unit')->unique()->values()->all();
        $datasets = [];
        $grouped = $trends->groupBy('dept_name');

        foreach ($grouped as $deptName => $records) {
            $dataPoints = [];
            foreach ($labels as $label) {
                $record = $records->firstWhere('time_unit', $label);
                $dataPoints[] = $record ? $record->total : 0;
            }
            $datasets[] = ['label' => $deptName, 'data' => $dataPoints];
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    public function getMetrics($filter, $startDate, $endDate)
    {
        $query = QuestionLog::query();
        $this->applyDateFilter($query, $filter, $startDate, $endDate);

        $totalFails = (clone $query)
            ->where('status', false)
            ->where('remark', 'No matching knowledge found') // 仅计算知识库缺失的
            ->count();

        $totalSuccess = (clone $query)->where('status', true)->count();

        return [
            'totalFail' => $totalFails,
            'totalSuccess' => $totalSuccess,
            'totalQuestions' => $totalFails + $totalSuccess,
        ];
    }

    private function applyDateFilter($query, $filter, $startDate, $endDate)
    {
        switch ($filter) {
            case 'daily':
                $query->whereDate('question_logs.created_at', Carbon::today());
                break;
            case 'weekly':
                $query->whereBetween('question_logs.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
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
                    $query->whereBetween('question_logs.created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ]);
                }
                break;
        }
    }
}
