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
        $filter = $request->input('filter', 'all'); // default = all time

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $query = QuestionLog::select(
            'faqs.question',
            DB::raw('COUNT(question_logs.faq_id) as total')
            )
            ->join('faqs', 'question_logs.faq_id', '=', 'faqs.id')
            ->where('question_logs.status', true);

        $queryIntent = QuestionLog::select(
            'intents.intent_name',
            DB::raw('COUNT(question_logs.intent_id) as total')
            )
            ->join('intents', 'question_logs.intent_id', '=', 'intents.id')
            ->where('question_logs.status', true);

        $queryDepartment = QuestionLog::select(
            'departments.name',
            DB::raw('COUNT(question_logs.department_id) as total')
        )
        ->join('departments','question_logs.department_id','=','departments.id')
        ->where('question_logs.status',true);

        // 🕒 Apply time-based filters
        switch ($filter) {
            case 'daily':
                $todayStart = Carbon::now()->startOfDay();
                $todayEnd   = Carbon::now()->endOfDay();
                $query->whereBetween('question_logs.created_at', [$todayStart->toDateTimeString(), $todayEnd->toDateTimeString()]);
                $queryIntent->whereBetween('question_logs.created_at', [$todayStart->toDateTimeString(), $todayEnd->toDateTimeString()]);
                $queryDepartment->whereBetween('question_logs.created_at', [$todayStart->toDateTimeString(), $todayEnd->toDateTimeString()]);
                break;
            case 'weekly':
                $startOfWeek = Carbon::now()->startOfWeek();
                $endOfWeek   = Carbon::now()->endOfWeek();
                $query->whereBetween('question_logs.created_at', [$startOfWeek->toDateTimeString(), $endOfWeek->toDateTimeString()]);
                $queryIntent->whereBetween('question_logs.created_at', [$startOfWeek->toDateTimeString(), $endOfWeek->toDateTimeString()]);
                $queryDepartment->whereBetween('question_logs.created_at', [$startOfWeek->toDateTimeString(), $endOfWeek->toDateTimeString()]);

                break;
            case 'monthly':
                $query->whereMonth('question_logs.created_at', Carbon::now()->month)
                    ->whereYear('question_logs.created_at', Carbon::now()->year);
                $queryIntent->whereMonth('question_logs.created_at', Carbon::now()->month)
                    ->whereYear('question_logs.created_at', Carbon::now()->year);
                $queryDepartment->whereMonth('question_logs.created_at', Carbon::now()->month)
                    ->whereYear('question_logs.created_at', Carbon::now()->year);
                break;
            case 'yearly':
                $query->whereYear('question_logs.created_at', Carbon::now()->year);
                $queryIntent->whereYear('question_logs.created_at', Carbon::now()->year);
                $queryDepartment->whereYear('question_logs.created_at', Carbon::now()->year);
                break;
            case 'custom-range':
                if ($startDate && $endDate) {
                    // Ensure dates are correctly formatted for database comparison and include time boundaries
                    $start = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
                    $end = Carbon::parse($endDate)->endOfDay()->toDateTimeString();

                    $query->whereBetween('question_logs.created_at', [$start, $end]);
                    $queryIntent->whereBetween('question_logs.created_at', [$start, $end]);
                    $queryDepartment->whereBetween('question_logs.created_at', [$start, $end]);
                }
                break;
            default:
                // all time (no filter)
                break;
        }

    // 🔢 Group & order
        $top10Faqs = $query->groupBy('faqs.question', 'faqs.id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $totalIntent = $queryIntent->groupBy('intents.intent_name','intents.id')
            ->orderByDesc('total')
            ->get();

        $totalDepartment = $queryDepartment->groupBy('departments.name','departments.id')
            ->orderByDesc('total')
            ->get();

        $data = [
            'Faq'=>$top10Faqs,
            'Intent'=>$totalIntent,
            'Department'=> $totalDepartment
        ];

        return response()->json($data);
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

    public function getDashboardMetrics()
    {

        $totalFails = QuestionLog::where('status', false)->count();
        $totalSuccess = QuestionLog::where('status', true)->count();


        $totalQuestions = $totalFails + $totalSuccess;


        $metrics = [
            'totalFail' => $totalFails,
            'totalSuccess' => $totalSuccess,
            'totalQuestions' => $totalQuestions,
        ];

        return response()->json($metrics);
    }


}
