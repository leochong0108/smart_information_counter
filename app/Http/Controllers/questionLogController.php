<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuestionLog;

class questionLogController extends Controller
{
    public function index()
    {
        $questionLogs = QuestionLog::all();
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

    public function exportExcel()
    {
        // Placeholder for Excel export functionality
        return response()->json(['message' => 'Excel export not implemented yet'], 501);
    }
}
