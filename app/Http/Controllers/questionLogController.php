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

    public function exportExcel()
    {
        // Placeholder for Excel export functionality
        return response()->json(['message' => 'Excel export not implemented yet'], 501);
    }
}
