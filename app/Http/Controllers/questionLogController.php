<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuestionLog;
use App\Models\Faq;



class questionLogController extends Controller
{
    public function index()
    {
        $questionLogs = QuestionLog::with(['intent', 'department', 'faq'])->get();
        return response()->json($questionLogs);
    }

    public function show($id)
    {
        $questionLog = QuestionLog::find($id);
        if (!$questionLog) {
            return response()->json(['message' => 'Question Log not found'], 404);
        }
        return response()->json($questionLog);
    }

    public function deleteAll()
    {
        QuestionLog::truncate();
        return response()->json(['message' => 'All question logs deleted successfully']);
    }

    public function selectFail()
    {
        $failedLogs = QuestionLog::where('status', false)
        ->where('checked',false)
        ->get();
        return response()->json($failedLogs);
    }

    public function markSelectedAsChecked(Request $request)
    {
        // 1. Validate the request to ensure 'ids' is present and is an array of integers
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:question_logs,id', // Checks IDs are integers and exist in the table
        ]);

        $ids = $request->input('ids');

        // 2. Perform a single mass update query
        $count = QuestionLog::whereIn('id', $ids)
                           ->update(['checked' => true]);

        if ($count === 0) {
            return response()->json(['message' => 'No question logs found or updated.'], 404);
        }

        return response()->json([
            'message' => "{$count} Question Logs marked as checked successfully.",
            'updated_count' => $count
        ]);
    }

    public function insertAndMark(Request $request, $id){

        $questionLog = QuestionLog::find($id);

        if(!$questionLog){
            return response()->json(['message' => 'Question Log not found'], 404);
        }

        $request->merge([
            'intent_id' => $request->intent_id === 'null' || $request->intent_id === '' ? null : $request->intent_id,
            'department_id' => $request->department_id === 'null' || $request->department_id === '' ? null : $request->department_id,
        ]);

        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'intent_id' => 'nullable|exists:intents,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $faq = Faq::create($request->all());

        $Marked =QuestionLog::where('id', $id)
        ->update(['checked' => true]);

        return response()->json([

            'message' => 'FAQ created and Question Log marked successfully.',
            'question_log_status' => [
                'id_marked' => (int)$id,
                'rows_updated' => $Marked,
            ],
            'new_faq' => $faq,

        ], 201);

    }

    public function exportExcel()
    {
        // Placeholder for Excel export functionality
        return response()->json(['message' => 'Excel export not implemented yet'], 501);
    }

        // 获取所有 "已请求" 但 "未回复" 的记录
    public function getPendingSupportRequests()
    {
        $requests = QuestionLog::where('help_requested', true)
            ->whereNull('admin_reply') // 只看还没回复的
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    // 管理员提交回复
    public function replyToSupportRequest(Request $request)
    {
        $logId = $request->input('log_id');
        $replyText = $request->input('reply');

        $log = QuestionLog::find($logId);
        if ($log) {
            $log->update([
                'admin_reply' => $replyText,
                'replied_at' => now()
                // 注意：我们不改 status，保持它是 false，因为它确实是 AI 回答失败的记录
            ]);
        }

        return response()->json(['status' => 'success']);
    }
}
