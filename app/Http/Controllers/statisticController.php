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

    public function totalQuestions()
    {
        $totalQuestions = QuestionLog::count();

        return response()->json($totalQuestions);
    }

    public function selectMost10(Request $request)
    {
        $filter = $request->input('filter', 'all'); // default = all time

        $query = QuestionLog::select(
                'faqs.question',
                DB::raw('COUNT(question_logs.faq_id) as total')
            )
            ->join('faqs', 'question_logs.faq_id', '=', 'faqs.id')
            ->where('question_logs.status', true);

        // 🕒 Apply time-based filters
        switch ($filter) {
            case 'daily':
                $todayStart = Carbon::now()->startOfDay();
                $todayEnd   = Carbon::now()->endOfDay();
                $query->whereBetween('question_logs.created_at', [$todayStart->toDateTimeString(), $todayEnd->toDateTimeString()]);
                break;
            case 'weekly':
                $startOfWeek = Carbon::now()->startOfWeek();
                $endOfWeek   = Carbon::now()->endOfWeek();
                $query->whereBetween('question_logs.created_at', [$startOfWeek->toDateTimeString(), $endOfWeek->toDateTimeString()]);

                break;
            case 'monthly':
                $query->whereMonth('question_logs.created_at', Carbon::now()->month)
                    ->whereYear('question_logs.created_at', Carbon::now()->year);
                break;
            case 'semester':
                // Example: Jan–Jun = Semester 1, Jul–Dec = Semester 2
                $month = Carbon::now()->month;
                if ($month >= 1 && $month <= 6) {
                    $query->whereBetween('question_logs.created_at', [
                        Carbon::createFromDate(null, 1, 1)->startOfDay(),
                        Carbon::createFromDate(null, 6, 30)->endOfDay()
                    ]);
                } else {
                    $query->whereBetween('question_logs.created_at', [
                        Carbon::createFromDate(null, 7, 1)->startOfDay(),
                        Carbon::createFromDate(null, 12, 31)->endOfDay()
                    ]);
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

        return response()->json($top10Faqs);
    }

/*     public function selectMost10()
    {
        $top10Faqs = QuestionLog::select(
            'faqs.question',
            DB::raw('COUNT(question_logs.faq_id) as total')
            )
            ->join('faqs', 'question_logs.faq_id', '=', 'faqs.id')
            ->where('question_logs.status', true)
            ->groupBy('faqs.question','faqs.id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
        return response()->json($top10Faqs);
    } */

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

    public function selectPeriod()
    {

    }
}
