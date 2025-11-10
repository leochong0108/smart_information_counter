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

    public function chat(Request $request)
    {
        $userMessage = $request->input('message');
        $gemini = new \App\Services\GeminiService();

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

/*         $prompt = "
        You are a chatbot for Southern University College.
        When the user asks about tuition fees, locations, or other university services,
        use getFaqAnswer to fetch the most relevant information from the database.
        If unsure, you can still try to call getFaqAnswer.
        Otherwise, reply naturally.
        User said: '$userMessage'
        "; */

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
/*         if (is_array($response) && isset($response['function_call'])) {
            $functionCall = $response['function_call'];
            $functionName = $functionCall['name'] ?? null;

            // Gemini sometimes returns 'args' or 'arguments'
            $args = $functionCall['args'] ?? $functionCall['arguments'] ?? [];

            if ($functionName === 'getFaqAnswer') {
                $question = $args['question'] ?? $userMessage;

                // ✅ Use your fuzzy match logic here
                $answer = $this->getFaqAnswer($question);
                return response()->json(['reply' => $answer]);
            }
        } */

        if (is_array($response) && isset($response['function_call'])) {
            $functionCall = $response['function_call'];
            $functionName = $functionCall['name'] ?? null;
            $args = $functionCall['args'] ?? $functionCall['arguments'] ?? [];

            switch ($functionName) {
                case 'getFaqAnswer':
                    $question = $args['question'] ?? $userMessage;
                    $answer = $this->getFaqAnswer($question);
                    return response()->json(['reply' => $answer]);

                case 'getDepartmentInfo':
                    $name = $args['name'] ?? $userMessage;
                    $answer = $this->getDepartmentInfo($name);
                    return response()->json(['reply' => $answer]);
            }
        }

        // 🧠 Step 2: If no function call, still try fuzzy match
        $fallbackAnswer = $this->getFaqAnswer($userMessage);

        if ($fallbackAnswer !== "I couldn't find a matching FAQ for that question.") {
            return response()->json(['reply' => $fallbackAnswer]);
        }

        // 🗣️ Step 3: Otherwise, return Gemini's direct reply
        return response()->json(['reply' => is_string($response) ? $response : "Sorry, I don't have that information yet."]);
    }

}
