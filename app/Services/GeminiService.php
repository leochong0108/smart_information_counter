<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Added for better debugging

class GeminiService
{
    protected $apiKey;
    protected $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct()
    {
        // Use the model endpoint directly in the class
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Handles tool calling and initial text responses.
     * Includes the 'tools' payload when functions are provided.
     * * @param string $prompt The user's message or a system prompt.
     * @param array $functions An array of function declarations (for tool use).
     * @return array|string|null Returns a function_call array, a text string, or null on failure.
     */
    public function askGemini(string $prompt, array $functions = [])
    {
        $url = "{$this->endpoint}?key={$this->apiKey}";

        $payload = [
            "contents" => [[
                "parts" => [["text" => $prompt]]
            ]],
        ];

        // If functions are provided, add the tools payload
        if (!empty($functions)) {
            $payload["tools"] = [
                [
                    "function_declarations" => $functions
                ]
            ];
        }

        $response = Http::post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();

            // Check if a candidate exists
            if (!isset($data['candidates'][0]['content']['parts'][0])) {
                return "Model returned an empty response or was blocked by safety settings.";
            }

            $part = $data['candidates'][0]['content']['parts'][0];

            // Step 1: If Gemini suggests a function call
            if (isset($part['functionCall'])) {
                return [
                    // Changed key from 'functionCall' to match the chat function's logic
                    'function_call' => $part['functionCall']
                ];
            }

            // Step 2: Normal text reply
            if (isset($part['text'])) {
                return $part['text'];
            }

            // Should not happen for this model, but catches unexpected response types
            return "Model returned an unexpected part type.";
        }

        Log::error('Gemini API (askGemini) failed: ' . $response->body());
        return "Sorry, something went wrong with the Gemini API. (askGemini error)";
    }

    // --- NEW METHOD FOR NATURAL LANGUAGE REPHRASING ---

    /**
     * Generates a simple text response without allowing function calls.
     * Used for rephrasing factual data into natural language.
     * * @param string $prompt The prompt containing the raw data to be rephrased.
     * @return string The generated text reply, or an error message.
     */
    public function generateText(string $prompt): string
    {
        $url = "{$this->endpoint}?key={$this->apiKey}";

        $payload = [
            "contents" => [[
                "parts" => [["text" => $prompt]]
            ]],
            // Crucially, NO 'tools' or 'function_declarations' are included here.
        ];

        $response = Http::post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();

            // Simple text extraction
            return $data['candidates'][0]['content']['parts'][0]['text']
                ?? "Could not generate natural language reply.";
        }

        Log::error('Gemini API (generateText) failed: ' . $response->body());
        return "Sorry, a secondary API call for rephrasing failed.";
    }

    /**
     * 将文本转换为向量 (Embedding)
     * @param string $text
     * @return array|null 返回浮点数数组
     */
    public function generateEmbedding($text)
    {
        // 使用专门的 Embedding 模型
        $url = "https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key={$this->apiKey}";

        $payload = [
            "model" => "models/text-embedding-004",
            "content" => [
                "parts" => [
                    ["text" => $text]
                ]
            ]
        ];

        try {
            $response = Http::post($url, $payload);

            if ($response->successful()) {
                return $response->json()['embedding']['values'];
            }

            \Log::error('Embedding API Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            \Log::error('Embedding Exception: ' . $e->getMessage());
            return null;
        }
    }
}
