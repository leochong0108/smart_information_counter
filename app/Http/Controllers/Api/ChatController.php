<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Intent;
use App\Models\QuestionLog;

class ChatController extends Controller
{
    public function ask(Request $request)
    {
        $data = $request->validate([
            'question' => 'required|string',
        ]);

        $question = $data['question'];

        // Very simple intent matching example (improve later)
        $intent = Intent::where('intent_name', 'like', '%' . explode(' ', $question)[0] . '%')->first();

        $answer = "Sorry, I cannot find an answer yet. (This is a mock response.)";
        $departmentId = null;

        if ($intent) {
            $answer = "Matched intent: " . $intent->intent_name;
            $departmentId = $intent->department_id;
        }

        // Save log
        QuestionLog::create([
            'question_text' => $question,
            'answer_text' => $answer,
            'intent_id' => $intent ? $intent->id : null,
            'department_id' => $departmentId
        ]);

        return response()->json(['answer' => $answer]);
    }
}
