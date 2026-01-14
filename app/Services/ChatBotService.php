<?php

namespace App\Services;

use App\Models\QuestionLog;
use Illuminate\Support\Facades\Log;

class ChatBotService
{
    protected $gemini;
    protected $searcher;

    public function __construct(GeminiService $gemini, VectorSearchService $searcher)
    {
        $this->gemini = $gemini;
        $this->searcher = $searcher;
    }


    public function processUserMessage(string $userMessage): array
    {
        $functions = [
            [
                "name" => "getFaqAnswer",
                "description" => "Search for the most relevant FAQ from the database.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "question" => ["type" => "string", "description" => "User's question."]
                    ],
                    "required" => ["question"]
                ]
            ],
            [
                "name" => "getDepartmentInfo",
                "description" => "Get information about a department by name.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "name" => ["type" => "string", "description" => "Department name."]
                    ],
                    "required" => ["name"]
                ]
            ],
        ];

        $prompt = "
        You are a chatbot for Southern University College.
        Please reply in natural language using the knowledge base.
        If unsure, call getFaqAnswer.
        User said: '$userMessage'
        ";

        try {
            $response = $this->gemini->askGemini($prompt, $functions);

            if (is_array($response) && isset($response['function_call'])) {
                return $this->handleFunctionCall($response['function_call'], $userMessage);
            }

            Log::info("Gemini didn't call function, forcing fallback search for: " . $userMessage);
            return $this->handleFallbackSearch($userMessage);

        } catch (\Exception $e) {
            Log::error("ChatBotService Error: " . $e->getMessage());

            $errorReply = "Sorry, I am currently experiencing technical difficulties. Please try again later.";

            $rawMessage = $e->getMessage();
            $cleanRemark = "System Error";

            if (preg_match('/\{.*\}/s', $rawMessage, $matches)) {
                $jsonObj = json_decode($matches[0], true);

                if (isset($jsonObj['error']['message'])) {
                    $cleanRemark = "System Error: " . $jsonObj['error']['message'];
                }
            }
            else if (str_contains($rawMessage, '429')) {
                $cleanRemark = "System Error: Gemini API Quota Exceeded";
            }
            else if (str_contains($rawMessage, '500')) {
                $cleanRemark = "System Error: Google Server Error";
            }
            else {
                $cleanRemark = "System Error: " . substr($rawMessage, 0, 150) . '...';
            }

            $log = $this->logToDb($userMessage, $errorReply, false, $cleanRemark);

            return ['reply' => $errorReply, 'log_id' => $log->id, 'status' => false];
        }
    }

    private function handleFunctionCall(array $functionCall, string $originalQuestion): array
    {
        $functionName = $functionCall['name'] ?? null;
        $args = $functionCall['args'] ?? $functionCall['arguments'] ?? [];

        $knowledgeText = null;
        $logPayload = [];
        $remark = "Vector Search Success";

        switch ($functionName) {
            case 'getFaqAnswer':
                $q = $args['question'] ?? $originalQuestion;
                $results = $this->searcher->findRelevantFaqs($q);

                if (!empty($results)) {
                    $formatted = $this->formatFaqsForPrompt($results);
                    $knowledgeText = $formatted['context'];
                    $logPayload = $formatted['log_data'];
                }
                break;

            case 'getDepartmentInfo':
                $name = $args['name'] ?? $originalQuestion;
                $info = $this->searcher->findDepartmentInfo($name);
                if ($info) {
                    $knowledgeText = $info;
                    $remark = "Department Info Found";
                }
                break;
        }

        if ($knowledgeText) {
            $integrationPrompt = "The user asked: '{$originalQuestion}'. Info found: {$knowledgeText}. Please synthesize a natural response.";
            $naturalReply = $this->gemini->generateText($integrationPrompt);

            $log = $this->logToDb($originalQuestion, $naturalReply, true, $remark, $logPayload);
            return ['reply' => $naturalReply, 'log_id' => $log->id, 'status' => true];
        }

        return $this->handleFallbackSearch($originalQuestion);
    }

    private function handleFallbackSearch(string $question): array
    {
        $results = $this->searcher->findRelevantFaqs($question);

        if (!empty($results)) {
            $formatted = $this->formatFaqsForPrompt($results);

            $integrationPrompt = "The user asked: '{$question}'. Knowledge base found: '{$formatted['context']}'. Please rephrase naturally.";
            $naturalReply = $this->gemini->generateText($integrationPrompt);

            $log = $this->logToDb($question, $naturalReply, true, "Success (Fallback)", $formatted['log_data']);
            return ['reply' => $naturalReply, 'log_id' => $log->id, 'status' => true];
        }

        $finalFailMsg = "Sorry, I don't have information about that yet. Please ask the counter staff.";
        $log = $this->logToDb($question, $finalFailMsg, false, "No matching knowledge found");

        return ['reply' => $finalFailMsg, 'log_id' => $log->id, 'status' => false];
    }


    private function formatFaqsForPrompt(array $results): array
    {
        $context = "";
        $logData = [];

        foreach ($results as $match) {
            $f = $match['faq'];
            $score = number_format($match['score'] * 100, 1) . "%";

            $context .= "--- FAQ (Match: {$score}) ---\n";
            $context .= "Q: {$f->question}\n A: {$f->answer}\n";
            if ($f->department) {
                $context .= "Dept: {$f->department->name} at {$f->department->location}.\n";
            }

            $logData[] = [
                'faq_id' => $f->id,
                'intent_id' => $f->intent_id,
                'department_id' => $f->department_id,
            ];
        }

        return ['context' => $context, 'log_data' => $logData];
    }

    private function logToDb($question, $answer, $status, $remark = null, $metaData = [])
    {
        $faqId = $metaData[0]['faq_id'] ?? null;
        $intentId = $metaData[0]['intent_id'] ?? null;
        $deptId = $metaData[0]['department_id'] ?? null;

        return QuestionLog::create([
            'question_text' => $question,
            'answer_text' => $answer,
            'status' => $status,
            'checked' => $status ? true : false,
            'remark' => $remark,
            'faq_id' => $faqId,
            'intent_id' => $intentId,
            'department_id' => $deptId,
        ]);
    }

    public function generateSummary(array $stats): string
    {
        $dataString = json_encode($stats);
        $prompt = "
        You are a data analyst for a university helpdesk.
        Here is the dashboard data: {$dataString}
        Please write a professional, concise summary (max 100 words).
        Output pure text.
        ";
        return $this->gemini->generateText($prompt);
    }
}
