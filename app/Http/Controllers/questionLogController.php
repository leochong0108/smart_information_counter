<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuestionLog;
use App\Services\QuestionLogService;
use App\Http\Requests\StoreFaqRequest;

class QuestionLogController extends Controller
{
    protected $logService;

    public function __construct(QuestionLogService $logService)
    {
        $this->logService = $logService;
    }

    public function index()
    {
        $logs = QuestionLog::with(['intent', 'department', 'faq'])
                    ->orderBy('created_at', 'desc')
                    ->get();
        return response()->json($logs);
    }

    public function show($id)
    {
        $questionLog = QuestionLog::with(['intent', 'department', 'faq'])->find($id);
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
            ->where('checked', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($failedLogs);
    }

    public function markSelectedAsChecked(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:question_logs,id',
        ]);

        $count = $this->logService->markLogsAsChecked($request->input('ids'));

        if ($count === 0) {
            return response()->json(['message' => 'No logs updated.'], 404);
        }

        return response()->json([
            'message' => "{$count} logs marked as checked.",
            'updated_count' => $count
        ]);
    }

    public function insertAndMark(StoreFaqRequest $request, $id)
    {
        try {
            $result = $this->logService->convertLogToFaq($id, $request->validated());

            return response()->json([
                'message' => 'FAQ created and Log marked successfully.',
                'new_faq' => $result['faq'],
                'log_id' => $result['log']->id
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Question Log not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Conversion failed', 'error' => $e->getMessage()], 500);
        }
    }


    public function getPendingSupportRequests()
    {
        $requests = QuestionLog::where('help_requested', true)
            ->whereNull('admin_reply')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    public function replyToSupportRequest(Request $request)
    {
        $request->validate([
            'log_id' => 'required|exists:question_logs,id',
            'reply' => 'required|string'
        ]);

        $this->logService->replyToRequest(
            $request->input('log_id'),
            $request->input('reply')
        );

        return response()->json(['status' => 'success']);
    }
}
