<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiChatBotController;
use App\Http\Controllers\QuestionLogController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\IntentController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StatisticController;
use App\Services\ExcelService;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//gemini logic
Route::post('/chat', [AiChatBotController::class, 'chat']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/request-help', [App\Http\Controllers\AiChatBotController::class, 'requestHumanHelp']);
Route::post('/check-reply', [App\Http\Controllers\AiChatBotController::class, 'checkAdminReply']);
Route::get('/top10ForChat', [StatisticController::class, 'selectTop10ForChat']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('user', [AiChatBotController::class, 'userInfo']);
    Route::post('logout', [AiChatBotController::class, 'logOut']);
    Route::post('/generate-summary', [AiChatBotController::class, 'generateDashboardSummary']);


    Route::get('/allIntents', [IntentController::class, 'index']);
    Route::get('/findIntents/{id}', [IntentController::class, 'show']);
    Route::post('/createIntents', [IntentController::class, 'store']);
    Route::put('/updateIntents/{id}', [IntentController::class, 'update']);
    Route::delete('/deleteIntents/{id}', [IntentController::class, 'destroy']);

    Route::get('/allFaqs', [FaqController::class, 'index']);
    Route::get('/FindFaqs/{id}', [FaqController::class, 'show']);
    Route::post('/createFaqs', [FaqController::class, 'store']);
    Route::put('/updateFaqs/{id}', [FaqController::class, 'update']);
    Route::delete('/deleteFaqs/{id}', [FaqController::class, 'destroy']);
    Route::post('/importExcel', [ExcelService::class, 'importExcel']);

    Route::get('/allDepartments', [DepartmentController::class, 'index']);
    Route::get('/findDepartments/{id}', [DepartmentController::class, 'show']);
    Route::post('/createDepartments', [DepartmentController::class, 'store']);
    Route::put('/updateDepartments/{id}', [DepartmentController::class, 'update']);
    Route::delete('/deleteDepartments/{id}', [DepartmentController::class, 'destroy']);

    Route::get('/allQuestionLogs', [QuestionLogController::class, 'index']);
    Route::get('/findQuestionLogs/{id}', [QuestionLogController::class, 'show']);
    Route::get('/selectFailedLogs', [QuestionLogController::class, 'selectFail']);
    Route::post('/mark-failed-logs', [QuestionLogController::class, 'markSelectedAsChecked']);
    Route::post('/insertAndMark/{id}', [QuestionLogController::class, 'insertAndMark']);
    Route::get('/admin/support-requests', [App\Http\Controllers\QuestionLogController::class, 'getPendingSupportRequests']);
    Route::post('/admin/reply-support', [App\Http\Controllers\QuestionLogController::class, 'replyToSupportRequest']);


    Route::get('/top10Faqs', [StatisticController::class, 'selectMost10']);
    Route::get('/totalIntents', [StatisticController::class, 'totalIntents']);
    Route::get('/getDashboardMetrics', [StatisticController::class, 'getDashboardMetrics']);
    Route::get('/department-trend', [StatisticController::class, 'departmentTrend']);

});








































// gemini api
/* Route::post('/chat', function (Request $request) {
    $userMessage = $request->input('message');
    $gemini = new GeminiService();

    // Step 1: Fuzzy match FAQ
    $faqs = Faq::with(['intent', 'department'])->get();
    $bestMatch = null;
    $highestScore = 0;

    foreach ($faqs as $f) {
        similar_text(strtolower($userMessage), strtolower($f->question), $percent);
        if ($percent > $highestScore) {
            $highestScore = $percent;
            $bestMatch = $f;
        }
    }

    if ($bestMatch && $highestScore > 50) {
        // Build a "knowledge base" answer from FAQ + intent + department
        $kbAnswer = $bestMatch->answer;

        if ($bestMatch->department) {
            $kbAnswer .= " The {$bestMatch->department->name} is located at {$bestMatch->department->location}. You can contact them at {$bestMatch->department->contact_info}.";
        }

        if ($bestMatch->intent) {
            $kbAnswer .= " (This question falls under intent: {$bestMatch->intent->name}).";
        }

        $prompt = "
        The user asked: '{$userMessage}'.
        Based on the knowledge base, the best answer is: '{$kbAnswer}'.
        Please reply in natural language, using the knowledge base answer directly (don't invent new info).
        ";

        $answer = $gemini->askGemini($prompt);
        return response()->json(['reply' => $answer]);
    }

    // Step 2: Fallback → Ask Gemini directly
    $fallback = $gemini->askGemini("You are a Southern University College information assistant. Answer this question naturally: " . $userMessage);

    return response()->json(['reply' => $fallback ?? "Sorry, I don't have that information yet."]);
}); */



//chatgpt api
/* Route::post('/chat', function (Request $request) {
    $message = $request->input('message');

    // 1. Get intents + FAQs from DB
    $intents = Intent::with('faqs')->get();

    // 2. Prepare knowledge base as text
    $knowledge = "";
    foreach ($intents as $intent) {
        $knowledge .= "Intent: {$intent->name}\n";
        foreach ($intent->faqs as $faq) {
            $knowledge .= "- Q: {$faq->question}\n";
            $knowledge .= "- A: {$faq->answer}\n";
        }
        $knowledge .= "\n";
    }

    // 3. Ask AI with knowledge base
    $result = OpenAI::chat()->create([
        'model' => 'gpt-5-nano',
        'messages' => [
            [
                'role' => 'system',
                'content' => "You are a university smart information counter.
                Use the following intents and FAQs to answer user queries naturally.
                If no match is found, politely say you are not sure and suggest contacting the counter.

                Knowledge Base:
                $knowledge"
            ],
            ['role' => 'user', 'content' => $message],
        ],
    ]);

    $reply = $result['choices'][0]['message']['content'] ?? "Sorry, I couldn’t answer.";

    // 4. Save query log
    Query::create([
        'user_question' => $message,
        'ai_response' => $reply,
    ]);

    return response()->json([
        'reply' => $reply
    ]);
}); */

/*
Route::post('/chat', function (Request $request) {
    $message = $request->input('message');
    return response()->json([
        'reply' => "Mock reply for: " . $message
    ]);
});*/






