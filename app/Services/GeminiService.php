<?php

/* namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private $apiKey;
    private $endpoint;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    }

    // Call Gemini to generate text
    public function askGemini(string $prompt): ?string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->endpoint . '?key=' . $this->apiKey, [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            // Log raw response for debugging
            Log::info('Gemini response', $data);

            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        Log::error('Gemini API failed: ' . $response->body());
        return null;
    }

    // Dummy intent classifier (replace with DB or fine-tuning later)
    public function classifyIntent(string $message): array
    {
        $message = strtolower($message);

        if (str_contains($message, 'scholarship')) {
            return ['intent' => 'scholarship', 'confidence' => 0.9];
        }

        if (str_contains($message, 'department') || str_contains($message, 'where is')) {
            return ['intent' => 'department_location', 'confidence' => 0.8];
        }

        return ['intent' => 'unknown', 'confidence' => 0.5];
    }
} */




namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function askGemini($prompt, $functions = [])
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$this->apiKey}";

        $payload = [
            "contents" => [[
                "parts" => [["text" => $prompt]]
            ]],
        ];

        // If we defined functions, send them to Gemini
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

            // Step 1: If Gemini suggests a function call
            if (isset($data['candidates'][0]['content']['parts'][0]['functionCall'])) {
                return [
                    'function_call' => $data['candidates'][0]['content']['parts'][0]['functionCall']
                ];
            }

            // Step 2: Normal text reply
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }
        }

        return "Sorry, something went wrong with Gemini.";
    }
}
