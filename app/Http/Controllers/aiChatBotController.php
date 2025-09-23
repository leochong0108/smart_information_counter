<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\Intent;
use App\Models\Department;
use App\Services\GeminiService;

class aiChatBotController extends Controller
{
    public function chat(Request $request)
    {
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
            // Build a knowledge base answer from FAQ + intent + department
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
        //$fallback = $gemini->askGemini("You are a Southern University College information assistant. Answer this question naturally: " . $userMessage);

        return response()->json(['reply' => $fallback ?? "Sorry, I don't have that information yet. Please contact the university 011 812 8888 directly."]);
    }
}
