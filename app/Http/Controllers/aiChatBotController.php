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
    // public function chat(Request $request)
    // {
    //     $userMessage = $request->input('message');
    //     $gemini = new GeminiService();

    //     // Step 1: Fuzzy match FAQ
    //     $faqs = Faq::with(['intent', 'department'])->get();
    //     $bestMatch = null;
    //     $highestScore = 0;

    //     foreach ($faqs as $f) {
    //         similar_text(strtolower($userMessage), strtolower($f->question), $percent);
    //         if ($percent > $highestScore) {
    //             $highestScore = $percent;
    //             $bestMatch = $f;
    //         }
    //     }

    //     if ($bestMatch && $highestScore > 50) {
    //         // Build a knowledge base answer from FAQ + intent + department
    //         $kbAnswer = $bestMatch->answer;

    //         if ($bestMatch->department) {
    //             $kbAnswer .= " The {$bestMatch->department->name} is located at {$bestMatch->department->location}. You can contact them at {$bestMatch->department->contact_info}.";
    //         }

    //         if ($bestMatch->intent) {
    //             $kbAnswer .= " (This question falls under intent: {$bestMatch->intent->name}).";
    //         }

    //         $prompt = "
    //         The user asked: '{$userMessage}'.
    //         Based on the knowledge base, the best answer is: '{$kbAnswer}'.
    //         Please reply in natural language, using the knowledge base answer directly (don't invent new info).
    //         ";

    //         $answer = $gemini->askGemini($prompt);
    //         return response()->json(['reply' => $answer]);
    //     }

    //     // Step 2: Fallback → Ask Gemini directly
    //     //$fallback = $gemini->askGemini("You are a Southern University College information assistant. Answer this question naturally: " . $userMessage);

    //     return response()->json(['reply' => $fallback ?? "Sorry, I don't have that information yet. Please contact the university 011 812 8888 directly."]);
    // }

    private function getFaqAnswer($question)
        {
            $faqs = Faq::with(['intent', 'department'])->get();
            $bestMatch = null;
            $highestScore = 0;

            foreach ($faqs as $f) {
                similar_text(strtolower($question), strtolower($f->question), $percent);
                if ($percent > $highestScore) {
                    $highestScore = $percent;
                    $bestMatch = $f;
                }
            }

            if ($bestMatch && $highestScore > 50) {
                $answer = $bestMatch->answer;
                if ($bestMatch->department) {
                    $answer .= " The {$bestMatch->department->name} is located at {$bestMatch->department->location}. You can contact them at {$bestMatch->department->contact_info}.";
                }

                QuestionLog::create([
                    'question_text' => $question,
                    'answer_text' => $answer,
                    'faq_id' => $bestMatch->id,
                    'intent_id' => $bestMatch->intent_id,
                    'department_id' => $bestMatch->department_id,
                    'status' => true,
                    'checked' => true,
                ]);

                return $answer;
            }

            QuestionLog::create([
                'question_text' => $question,
                'status' => false,
                'checked' => false,
            ]);

            return "I couldn't find a matching FAQ for that question.";

        }

        private function getDepartmentInfo($name)
        {
            $department = Department::where('name', 'like', "%$name%")->first();
            if (!$department) {
                return "I couldn't find department information for '$name'.";
            }
            return "{$department->name} is located at {$department->location}. Contact: {$department->contact_info}.";
        }

        private function getIntentInfo($name)
        {
            $intent = Intent::where('name', 'like', "%$name%")->first();
            if (!$intent) {
                return "I couldn't find intent information for '$name'.";
            }
            return "Intent: {$intent->name} — {$intent->description}.";
        }

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
                    $factualAnswer = $this->getFaqAnswer($question);
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
                $integrationPrompt = "The user asked: '{$userMessage}'.
                The knowledge base provided the following information: '{$factualAnswer}'.
                You are a polite chatbot for Southern University College.
                Please rephrase and integrate this information into a single, natural, and conversational response.
                Do not add or invent any new information beyond the facts provided.
                Your final answer should be ONLY the natural language response.";

                // Assuming a method for simple text generation without tools:
                // **NOTE:** You must implement a simple $gemini->generateText($prompt) method
                // that doesn't use tools, just returns the text response.
                $naturalReply = $gemini->generateText($integrationPrompt);

                return response()->json(['reply' => $naturalReply]);
                // --------------------------------------------------------------------------
            }

            // If the function was called but failed (e.g., department not found),
            // return the failure message directly.
            if ($factualAnswer) {
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
            Do not add or invent any new information beyond the facts provided.
            Your final answer should be ONLY the natural language response.";

            $naturalReply = $gemini->generateText($integrationPrompt);

            return response()->json(['reply' => $naturalReply]);
        }

        // 🗣️ Step 4: Otherwise, return Gemini's direct reply or final error
        return response()->json(['reply' => is_string($response) ? $response : "Sorry, I don't have that information yet."]);
    }

}
