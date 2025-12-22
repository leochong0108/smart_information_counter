<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ChatBotService;
use App\Models\QuestionLog;

// 注意：类名改为 PascalCase
class AiChatBotController extends Controller
{
    protected $chatBotService;

    // 依赖注入 ChatBotService
    public function __construct(ChatBotService $chatBotService)
    {
        $this->chatBotService = $chatBotService;
    }

    /**
     * 聊天主入口
     */
    public function chat(Request $request)
    {
        $userMessage = $request->input('message');

        if (empty($userMessage)) {
            return response()->json(['reply' => 'Please type a question.', 'status' => false]);
        }

        // 所有逻辑都在 Service 里，Controller 只管拿结果返回
        $result = $this->chatBotService->processUserMessage($userMessage);

        return response()->json($result);
    }

    /**
     * 生成报表摘要
     */
    public function generateDashboardSummary(Request $request)
    {
        $stats = $request->input('stats');

        if (empty($stats)) {
            return response()->json(['summary' => 'No data available to analyze.']);
        }

        $summary = $this->chatBotService->generateSummary($stats);

        return response()->json(['summary' => $summary]);
    }

    /**
     * 请求人工协助 (简单逻辑可以保留在 Controller，或者也移入 Service)
     */
    public function requestHumanHelp(Request $request)
    {
        $logId = $request->input('log_id');
        QuestionLog::where('id', $logId)->update(['help_requested' => true]);
        return response()->json(['status' => 'success']);
    }

    /**
     * 检查管理员回复
     */
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
