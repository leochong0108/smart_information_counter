<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\Intent;
use App\Models\Department;
use App\Models\QuestionLog;
use App\Services\GeminiService;

class aiChatBotController extends Controller
{

    private function getFaqAnswer($question)
    {
        // ... (Fuzzy matching logic to find bestMatches and $topResults array) ...
        $faqs = Faq::with(['department'])->get();
        $bestMatches = []; // Changed to an array

        foreach ($faqs as $f) {
            similar_text(strtolower($question), strtolower($f->question), $percent);

            // Collect all matches above a moderate threshold (e.g., 60%)
            if ($percent > 60) {
                $bestMatches[] = [
                    'faq' => $f,
                    'score' => $percent
                ];
            }
        }

        // Sort by score descending and take the top 3
        usort($bestMatches, fn($a, $b) => $b['score'] <=> $a['score']);
        $topResults = array_slice($bestMatches, 0, 3);

        $context = "";
        $logData = []; // NEW: Array to store data for logging

        foreach ($topResults as $match) {
            $f = $match['faq'];
            // Build the context string for the LLM
            $context .= "--- Source FAQ ID {$f->id} (Score: {$match['score']}%) --- \n";
            $context .= "Question: {$f->question} \n";
            $context .= "Answer: {$f->answer} \n";
            // ... (Department Info) ...
            if ($f->department) {
                $context .= "Department Info: The {$f->department->name} is located at {$f->department->location}. Contact: {$f->department->contact_info}. \n";
            }
            $faq_ids[] = $f->id;

            // Store the relevant IDs and the raw answer text for later logging
            $logData[] = [
                'faq_id' => $f->id,
                'intent_id' => $f->intent_id,
                'department_id' => $f->department_id,
                'raw_answer' => $f->answer // Store the individual raw answer
            ];
        }

        if (empty($context)) {

            return "I couldn't find a matching FAQ for that question.";
        }

        // NEW RETURN STRUCTURE
        return [
            'factual_answer' => $context, // The string to send to Gemini
            'log_data' => $logData        // The structured data for the QuestionLog
        ];
    }

    private function getDepartmentInfo($name)
    {
        $department = Department::where('name', 'like', "%$name%")->first();
        if (!$department) {
            return "I couldn't find department information for '$name'.";
         }
        return "{$department->name} is located at {$department->location}. Contact: {$department->contact_info}.";
    }

/*         private function getIntentInfo($name)
        {
            $intent = Intent::where('name', 'like', "%$name%")->first();
            if (!$intent) {
                return "I couldn't find intent information for '$name'.";
            }
            return "Intent: {$intent->name} — {$intent->description}.";
        } */

// ... (Helper functions getFaqAnswer, getDepartmentInfo, getIntentInfo remain unchanged)

    public function chat(Request $request)
    {
        $userMessage = $request->input('message');
        $gemini = new \App\Services\GeminiService(); // Assuming this is your service class

        // Functions array remains unchanged
        $functions = [
            [
                "name" => "getFaqAnswer",
                "description" => "Search for the most relevant FAQ from the database and return its answer.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "question" => [
                            "type" => "string",
                            "description" => "User's question about the university."
                        ]
                    ],
                    "required" => ["question"]
                ]
            ],
            [
                "name" => "getDepartmentInfo",
                "description" => "Get information about a department by name (like location or contact).",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "name" => [
                            "type" => "string",
                            "description" => "The name of the department (e.g., IT, Business, Engineering)."
                        ]
                    ],
                    "required" => ["name"]
                ]
            ],
        ];

        $prompt = "
        You are a chatbot for Southern University College.
        Please reply in natural language and politely, using the knowledge base answer directly (don't invent new info).
        You can call one of these functions when appropriate:
        - getFaqAnswer: when user asks a general question about the university, fees, or facilities.
        - getDepartmentInfo: when user asks about a specific department (location, contact, etc.).

        If you're unsure which to call, pick getFaqAnswer by default.
        User said: '$userMessage'
        ";

        $response = $gemini->askGemini($prompt, $functions);

        // 🧩 Step 1: Detect if Gemini returned a function call
        if (is_array($response) && isset($response['function_call'])) {
            $functionCall = $response['function_call'];
            $functionName = $functionCall['name'] ?? null;
            $args = $functionCall['args'] ?? $functionCall['arguments'] ?? [];

            $factualAnswer = null; // Initialize a variable to hold the raw data
            $departmentFailureMessage = null;

            switch ($functionName) {
                case 'getFaqAnswer':
                        $question = $args['question'] ?? $userMessage;
                        // The return value is now an array or a string failure message
                        $functionResult = $this->getFaqAnswer($question);

                        $factualAnswer = null;
                        $logPayload = []; // NEW: Variable to hold the log data

                        if (is_array($functionResult)) {
                            $factualAnswer = $functionResult['factual_answer'];
                            $logPayload = $functionResult['log_data']; // Store the IDs/raw answer
                        } else {
                            // It's a failure string message
                            $factualAnswer = $functionResult;
                        }
                        break;

                case 'getDepartmentInfo':
                    $name = $args['name'] ?? $userMessage;
                    $factualAnswer = $this->getDepartmentInfo($name);
                    // prepare the specific failure message so later checks don't reference undefined $name
                    $departmentFailureMessage = "I couldn't find department information for '{$name}'.";
                    break;
            }

            // 🧠 Step 2 (NEW): If we successfully retrieved factual data, send it back to Gemini for rephrasing
            $failureMessage = "I couldn't find a matching FAQ for that question.";

            // **CHECK:** Did a function execute and return valid, non-failure data?
            if ($factualAnswer && $factualAnswer !== $failureMessage && $factualAnswer !== $departmentFailureMessage) {

                // --- THIS IS WHERE THE NATURAL LANGUAGE INTEGRATION HAPPENS (THE KEY CHANGE) ---
                \Log::info($factualAnswer);
                $integrationPrompt = "The user asked: '{$userMessage}'.

                I have retrieved the following pieces of information from the knowledge base, separated by '---':
                {$factualAnswer} // This now contains the combined context string

                You are a polite chatbot for Southern University College.
                Please synthesize a single, natural, and comprehensive response by using ALL relevant facts from the retrieved information.
                If any piece of information is clearly irrelevant to the user's question, ignore it.
                Do not add or invent any new information.
                Your final answer should be ONLY the natural language response.";

                            // Assuming a method for simple text generation without tools:
                            // **NOTE:** You must implement a simple $gemini->generateText($prompt) method
                            // that doesn't use tools, just returns the text response.
                    $naturalReply = $gemini->generateText($integrationPrompt);

                if (!empty($logPayload)) {
                    // You can choose to log one entry per retrieved FAQ or combine them.
                    // To log the FINAL $naturalReply, we'll log one entry for the main/highest-scoring FAQ.

                    $mainMatch = $logPayload[0]; // Assuming the first item is the best match
                    \Log::info($mainMatch);

                    QuestionLog::create([
                        'question_text' => $question,
                        'answer_text' => $naturalReply, // Log the final, natural answer
                        'faq_id' => $mainMatch['faq_id'],
                        'intent_id' => $mainMatch['intent_id'],
                        'department_id' => $mainMatch['department_id'],
                        'status' => true,
                        'checked' => true,
                        // Optionally, log the other FAQ IDs used in the context as well
                        // 'context_faq_ids' => json_encode(array_column($logPayload, 'faq_id')),
                    ]);

                }

                return response()->json(['reply' => $naturalReply]);
                // --------------------------------------------------------------------------
            }

            // If the function was called but failed (e.g., department not found),
            // return the failure message directly.
            if ($factualAnswer) {

                QuestionLog::create([
                    'question_text' => $question,
                    'status' => false,
                    'checked' => false,
                ]);

                return response()->json(['reply' => $factualAnswer]);
            }
        }

        // 🧠 Step 3: If no function call, still try fuzzy match (Fallback Logic)
        $fallbackAnswer = $this->getFaqAnswer($userMessage);

        if ($fallbackAnswer !== "I couldn't find a matching FAQ for that question.") {
            // If the fallback works, we still use Gemini to make it sound natural!

            // --- NEW REPHRASING FOR FALLBACK ---
            $integrationPrompt = "The user asked: '{$userMessage}'.
            The knowledge base provided the following information: '{$fallbackAnswer}'.
            You are a polite chatbot for Southern University College.
            Please rephrase and integrate this information into a single, natural, and conversational response.
            Do not add or invent any new information beyond the facts provided, always greeting before answer like Hi,then asnwer.
            Your final answer should be ONLY the natural language response.";

            $naturalReply = $gemini->generateText($integrationPrompt);

            if (!empty($logPayload)) {
                    // You can choose to log one entry per retrieved FAQ or combine them.
                    // To log the FINAL $naturalReply, we'll log one entry for the main/highest-scoring FAQ.

                $mainMatch = $logPayload[0]; // Assuming the first item is the best match

                QuestionLog::create([
                    'question_text' => $question,
                    'answer_text' => $naturalReply, // Log the final, natural answer
                    'faq_id' => $mainMatch['faq_id'],
                    'intent_id' => $mainMatch['intent_id'],
                    'department_id' => $mainMatch['department_id'],
                    'status' => true,
                    'checked' => true,
                        // Optionally, log the other FAQ IDs used in the context as well
                        // 'context_faq_ids' => json_encode(array_column($logPayload, 'faq_id')),
                ]);

            }


            return response()->json(['reply' => $naturalReply]);
        }

        // 🗣️ Step 4: Otherwise, return Gemini's direct reply or final error
        return response()->json(['reply' => is_string($response) ? $response : "Sorry, I don't have that information yet."]);
    }

    public function generateDashboardSummary(Request $request)
    {
        // 1. 接收前端传来的统计数据 (JSON)
        $stats = $request->input('stats');
        // $stats 结构大概是: { total: 100, top_intent: 'Wifi Issue', top_dept: 'IT', ... }

        // 2. 构建 Prompt
        // 注意：把数据转成字符串塞进 Prompt
        $dataString = json_encode($stats);

        $prompt = "
        You are a data analyst for a university helpdesk.
        Here is the dashboard data for the selected period:
        {$dataString}

        Please write a professional, concise summary (max 100 words) for a management report.
        Structure:
        1. Highlight the total volume and success rate.
        2. Point out the most critical issue (Top Intent).
        3. Give 1 brief recommendation based on the data.

        Output pure text, no markdown formatting.
        ";

        // 3. 调用 Gemini (复用你现有的 Service)
        $gemini = new \App\Services\GeminiService();
        $analysis = $gemini->generateText($prompt); // 假设你有这个简单生成文本的方法

        return response()->json(['summary' => $analysis]);
    }

}
