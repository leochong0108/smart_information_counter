<?php

use App\Http\Controllers\aiChatBotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Services\GeminiService;
use App\Models\Intent;
use App\Models\Faq;
use App\Models\Department;
use App\Models\Query;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\aiChatBotControllerr;
use App\Http\Controllers\questionLogController;
use App\Http\Controllers\departmentController;
use App\Http\Controllers\intentController;
use App\Http\Controllers\faqController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//gemini logic
Route::post('/chat', [aiChatBotController::class, 'chat']);

Route::get('/allIntents', [intentController::class, 'index']);
Route::get('/findIntents/{id}', [intentController::class, 'show']);
Route::post('/createIntents', [intentController::class, 'store']);
Route::put('/updateIntents/{id}', [intentController::class, 'update']);
Route::delete('/deleteIntents/{id}', [intentController::class, 'destroy']);

Route::get('/allFaqs', [faqController::class, 'index']);
Route::get('/FindFaqs/{id}', [faqController::class, 'show']);
Route::post('/createFaqs', [faqController::class, 'store']);
Route::put('/updateFaqs/{id}', [faqController::class, 'update']);
Route::delete('/deleteFaqs/{id}', [faqController::class, 'destroy']);

Route::get('/allDepartments', [departmentController::class, 'index']);
Route::get('/findDepartments/{id}', [departmentController::class, 'show']);
Route::post('/createDepaertments', [departmentController::class, 'store']);
Route::put('/updateDepartments/{id}', [departmentController::class, 'update']);
Route::delete('/deleteDepartments/{id}', [departmentController::class, 'destroy']);

Route::get('/allQuestionLogs', [questionLogController::class, 'index']);
Route::get('/findQuestionLogs/{id}', [questionLogController::class, 'show']);








































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






