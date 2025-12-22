<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DashboardService;
use App\Models\QuestionLog;
use Illuminate\Support\Facades\DB;

class StatisticController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function selectMost10(Request $request)
    {
        $data = $this->dashboardService->getTopStats(
            $request->input('filter', 'all'),
            $request->input('startDate'),
            $request->input('endDate')
        );

        return response()->json($data);
    }

    public function departmentTrend(Request $request)
    {
        $data = $this->dashboardService->getDepartmentTrend(
            $request->input('filter', 'all'),
            $request->input('startDate'),
            $request->input('endDate')
        );

        return response()->json($data);
    }

    public function getDashboardMetrics(Request $request)
    {
        $metrics = $this->dashboardService->getMetrics(
            $request->input('filter', 'all-time'),
            $request->input('startDate'),
            $request->input('endDate')
        );

        return response()->json($metrics);
    }

    // 这些简单查询可以保留在 Controller，或者也移入 Service
    public function selectTop10ForChat()
    {
        $top10Faqs = QuestionLog::select('faqs.question', DB::raw('COUNT(question_logs.faq_id) as total'))
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
        $intents = QuestionLog::select('intents.intent_name', DB::raw('COUNT(question_logs.intent_id) as total'))
            ->join('intents', 'question_logs.intent_id', '=', 'intents.id')
            ->where('question_logs.status', true)
            ->groupBy('intents.intent_name','intents.id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return response()->json($intents);
    }
}
