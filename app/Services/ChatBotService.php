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

    /**
     * 处理用户聊天的核心逻辑
     */
    public function processUserMessage(string $userMessage): array
    {
        // 定义 Tools (Function Definitions)
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
            // 1. 第一次调用 Gemini
            $response = $this->gemini->askGemini($prompt, $functions);

            // 2. 检查是否触发了 Function Call
            if (is_array($response) && isset($response['function_call'])) {
                return $this->handleFunctionCall($response['function_call'], $userMessage);
            }

            // 3. 如果 Gemini 没有调用函数，直接Fallback到强制搜索 (或者直接回复)
            Log::info("Gemini didn't call function, forcing fallback search for: " . $userMessage);
            return $this->handleFallbackSearch($userMessage);

        } catch (\Exception $e) {
            // 记录原始错误到系统日志 (供开发者 debug)
            Log::error("ChatBotService Error: " . $e->getMessage());

            $errorReply = "Sorry, I am currently experiencing technical difficulties. Please try again later.";

            // --- 🧹 开始清洗错误信息 (为了存入 DB 时好看) ---
            $rawMessage = $e->getMessage();
            $cleanRemark = "System Error"; // 默认值

            // 1. 尝试从错误信息中提取 JSON 部分
            // 正则解释：匹配第一个 { 开始到最后一个 } 结束的内容
            if (preg_match('/\{.*\}/s', $rawMessage, $matches)) {
                $jsonObj = json_decode($matches[0], true);

                // 如果提取到了具体的 error message
                if (isset($jsonObj['error']['message'])) {
                    $cleanRemark = "System Error: " . $jsonObj['error']['message'];
                }
            }
            // 2. 如果解析 JSON 失败，但在字符串里发现了常见的 HTTP 状态码
            else if (str_contains($rawMessage, '429')) {
                $cleanRemark = "System Error: Gemini API Quota Exceeded";
            }
            else if (str_contains($rawMessage, '500')) {
                $cleanRemark = "System Error: Google Server Error";
            }
            else {
                // 3. 实在解析不了，就截取前 150 个字符
                $cleanRemark = "System Error: " . substr($rawMessage, 0, 150) . '...';
            }
            // --- 🧹 清洗结束 ---

            // 将清洗后的 cleanRemark 存入数据库
            $log = $this->logToDb($userMessage, $errorReply, false, $cleanRemark);

            return ['reply' => $errorReply, 'log_id' => $log->id, 'status' => false];
        }
    }

    /**
     * 处理 Function Call 逻辑
     */
    private function handleFunctionCall(array $functionCall, string $originalQuestion): array
    {
        $functionName = $functionCall['name'] ?? null;
        $args = $functionCall['args'] ?? $functionCall['arguments'] ?? [];

        $knowledgeText = null;
        $logPayload = [];
        $remark = "Vector Search Success";

        // 分发逻辑
        switch ($functionName) {
            case 'getFaqAnswer':
                $q = $args['question'] ?? $originalQuestion;
                // 调用 VectorSearchService
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

        // 如果找到了知识
        if ($knowledgeText) {
            $integrationPrompt = "The user asked: '{$originalQuestion}'. Info found: {$knowledgeText}. Please synthesize a natural response.";
            $naturalReply = $this->gemini->generateText($integrationPrompt);

            $log = $this->logToDb($originalQuestion, $naturalReply, true, $remark, $logPayload);
            return ['reply' => $naturalReply, 'log_id' => $log->id, 'status' => true];
        }

        // 如果 Function 没找到结果，走 Fallback
        return $this->handleFallbackSearch($originalQuestion);
    }

    /**
     * Fallback: 强制搜索
     */
    private function handleFallbackSearch(string $question): array
    {
        // 强制调用 VectorService (它内部包含了 Vector -> Fuzzy 的降级逻辑)
        $results = $this->searcher->findRelevantFaqs($question);

        if (!empty($results)) {
            $formatted = $this->formatFaqsForPrompt($results);

            $integrationPrompt = "The user asked: '{$question}'. Knowledge base found: '{$formatted['context']}'. Please rephrase naturally.";
            $naturalReply = $this->gemini->generateText($integrationPrompt);

            $log = $this->logToDb($question, $naturalReply, true, "Success (Fallback)", $formatted['log_data']);
            return ['reply' => $naturalReply, 'log_id' => $log->id, 'status' => true];
        }

        // 彻底失败
        $finalFailMsg = "Sorry, I don't have information about that yet. Please ask the counter staff.";
        $log = $this->logToDb($question, $finalFailMsg, false, "No matching knowledge found");

        return ['reply' => $finalFailMsg, 'log_id' => $log->id, 'status' => false];
    }

    /**
     * 将搜索结果格式化为字符串给 LLM，并提取日志数据
     */
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

    /**
     * 日志记录
     */
    private function logToDb($question, $answer, $status, $remark = null, $metaData = [])
    {
        // 提取第一条匹配的元数据
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

    /**
     * 生成 Dashboard 摘要 (原 Controller 里的逻辑)
     */
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
