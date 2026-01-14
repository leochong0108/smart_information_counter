<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Added for better debugging

class GeminiService
{
    protected $apiKey;
    protected $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function askGemini(string $prompt, array $functions = [])
    {
        $url = "{$this->endpoint}?key={$this->apiKey}";

        $payload = [
            "contents" => [[
                "parts" => [["text" => $prompt]]
            ]],
        ];

        if (!empty($functions)) {
            $payload["tools"] = [
                [
                    "function_declarations" => $functions
                ]
            ];
        }

        $response = Http::retry(3, 100)->post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();

            if (!isset($data['candidates'][0]['content']['parts'][0])) {
                return "Model returned an empty response or was blocked by safety settings.";
            }

            $part = $data['candidates'][0]['content']['parts'][0];

            if (isset($part['functionCall'])) {
                return [
                    'function_call' => $part['functionCall']
                ];
            }

            if (isset($part['text'])) {
                return $part['text'];
            }

            return "Model returned an unexpected part type.";
        }
        throw new \Exception("Gemini API Error: " . $response->body());
    }



    public function generateText(string $prompt): string
    {
        $url = "{$this->endpoint}?key={$this->apiKey}";

        $payload = [
            "contents" => [[
                "parts" => [["text" => $prompt]]
            ]],
        ];

        $response = Http::post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();

            return $data['candidates'][0]['content']['parts'][0]['text']
                ?? "Could not generate natural language reply.";
        }

        throw new \Exception("Gemini API Error (GenText): " . $response->body());
    }


    public function generateEmbedding($text)
    {
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
