<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ChatBotService;
use App\Models\QuestionLog;

class AiChatBotController extends Controller
{
    protected $chatBotService;

    public function __construct(ChatBotService $chatBotService)
    {
        $this->chatBotService = $chatBotService;
    }

    public function chat(Request $request)
    {
        $userMessage = $request->input('message');

        if (empty($userMessage)) {
            return response()->json(['reply' => 'Please type a question.', 'status' => false]);
        }

        $result = $this->chatBotService->processUserMessage($userMessage);

        return response()->json($result);
    }


    public function generateDashboardSummary(Request $request)
    {
        $stats = $request->input('stats');

        if (empty($stats)) {
            return response()->json(['summary' => 'No data available to analyze.']);
        }

        $summary = $this->chatBotService->generateSummary($stats);

        return response()->json(['summary' => $summary]);
    }

    public function requestHumanHelp(Request $request)
    {
        $logId = $request->input('log_id');
        QuestionLog::where('id', $logId)->update(['help_requested' => true]);
        return response()->json(['status' => 'success']);
    }

    public function checkAdminReply(Request $request)
    {
        $logId = $request->input('log_id');
        $log = QuestionLog::where('id', $logId)->first();

        if ($log && $log->admin_reply) {
            return response()->json([
                'replied' => true,
                'reply' => $log->admin_reply
            ]);
        }
        return response()->json(['replied' => false]);
    }
}
