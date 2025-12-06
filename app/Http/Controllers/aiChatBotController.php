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

/*     private function getFaqAnswer($question)
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
    } */

    // 🧮 数学辅助函数：计算余弦相似度
    // 两个向量越像，结果越接近 1；越不像，结果越接近 0
    private function cosineSimilarity(array $vecA, array $vecB)
    {
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        foreach ($vecA as $key => $value) {
            if (!isset($vecB[$key])) continue; // 防止数组长度不一致报错
            $dotProduct += $value * $vecB[$key];
            $magnitudeA += $value ** 2;
            $magnitudeB += $vecB[$key] ** 2;
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        return ($magnitudeA * $magnitudeB) == 0 ? 0 : $dotProduct / ($magnitudeA * $magnitudeB);
    }

// 1️⃣ 主入口方法：尝试向量搜索，不行就转模糊匹配
    private function getFaqAnswer($question)
    {
        $gemini = new \App\Services\GeminiService();

        // 🟢 尝试生成向量
        $questionEmbedding = $gemini->generateEmbedding($question);

        // 🚨 如果生成失败（网络问题/API错误），直接降级使用模糊匹配
        if (!$questionEmbedding) {
            \Log::warning("Embedding generation failed, falling back to fuzzy search.");
            return $this->getFaqAnswerFuzzy($question);
        }

        // 🔵 开始向量搜索逻辑
        $faqs = Faq::whereNotNull('embedding')->with(['department'])->get();
        $bestMatches = [];

        foreach ($faqs as $f) {
            $dbEmbedding = json_decode($f->embedding, true);
            if (!is_array($dbEmbedding)) continue;

            $score = $this->cosineSimilarity($questionEmbedding, $dbEmbedding);

            // 阈值：0.65 (可以微调)
            if ($score > 0.65) {
                $bestMatches[] = ['faq' => $f, 'score' => $score];
            }
        }

        // 🚨 即使向量生成成功了，如果所有 FAQ 的相似度都很低（没找到匹配），也可以考虑降级
        if (empty($bestMatches)) {
            // 这里你可以选择直接返回失败，或者也试一下模糊匹配
            // 通常如果向量都找不到，模糊匹配更找不到，但为了保险可以加上：
            return $this->getFaqAnswerFuzzy($question);
        }

        // 排序并取前 3
        usort($bestMatches, fn($a, $b) => $b['score'] <=> $a['score']);
        $topResults = array_slice($bestMatches, 0, 3);

        return $this->formatFaqResponse($topResults);
    }

    // 2️⃣ 旧的模糊匹配逻辑 (完全照搬你原来的代码，只需改改名字)
    private function getFaqAnswerFuzzy($question)
    {
        $faqs = Faq::with(['department'])->get();
        $bestMatches = [];

        foreach ($faqs as $f) {
            similar_text(strtolower($question), strtolower($f->question), $percent);

            // 阈值：60%
            if ($percent > 60) {
                $bestMatches[] = ['faq' => $f, 'score' => $percent]; // 注意这里的 score 是 0-100
            }
        }

        if (empty($bestMatches)) {
            return "I couldn't find a matching FAQ for that question.";
        }

        // 排序并取前 3
        usort($bestMatches, fn($a, $b) => $b['score'] <=> $a['score']);
        $topResults = array_slice($bestMatches, 0, 3);

        return $this->formatFaqResponse($topResults);
    }

    // 3️⃣ 辅助方法：统一格式化输出 (DRY 原则，避免重复代码)
    // 无论是向量搜索还是模糊匹配，最后生成 Prompt 的逻辑是一样的
    private function formatFaqResponse($topResults)
    {
        $context = "";
        $logData = [];

        foreach ($topResults as $match) {
            $f = $match['faq'];
            // 兼容分数显示：向量是 0.85，模糊匹配是 85，这里简单处理一下显示
            $displayScore = $match['score'] > 1 ? $match['score'] . "%" : number_format($match['score'], 2);

            $context .= "--- Source FAQ (Similarity: {$displayScore}) --- \n";
            $context .= "Question: {$f->question} \n";
            $context .= "Answer: {$f->answer} \n";

            if ($f->department) {
                $context .= "Dept: {$f->department->name} at {$f->department->location}. \n";
            }

            $logData[] = [
                'faq_id' => $f->id,
                'intent_id' => $f->intent_id,
                'department_id' => $f->department_id,
                'raw_answer' => $f->answer
            ];
        }

        return [
            'factual_answer' => $context,
            'log_data' => $logData
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


    public function chat(Request $request)
    {
        $userMessage = $request->input('message');

        // 1. 基础验证
        if (empty($userMessage)) {
            return response()->json(['reply' => 'Please type a question.', 'status' => false]);
        }

        $gemini = new \App\Services\GeminiService();

        // 定义 Functions (保持你原有的)
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
            // 2. 调用 Gemini API
            $response = $gemini->askGemini($prompt, $functions);

            // 🔍 情况 A: Gemini 返回了 Function Call
            if (is_array($response) && isset($response['function_call'])) {
                $functionCall = $response['function_call'];
                $functionName = $functionCall['name'] ?? null;
                $args = $functionCall['args'] ?? $functionCall['arguments'] ?? [];

                $factualAnswer = null;
                $logPayload = [];
                $remark = "Vector Search Success"; // 默认备注

                // 执行对应的 PHP 函数
                switch ($functionName) {
                    case 'getFaqAnswer':
                        $q = $args['question'] ?? $userMessage;
                        $result = $this->getFaqAnswer($q); // 这里会尝试向量搜索

                        if (is_array($result)) {
                            $factualAnswer = $result['factual_answer'];
                            $logPayload = $result['log_data'];
                        } else {
                            // 如果 getFaqAnswer 返回字符串，说明没找到
                            $factualAnswer = null;
                        }
                        break;

                    case 'getDepartmentInfo':
                        $name = $args['name'] ?? $userMessage;
                        $res = $this->getDepartmentInfo($name);
                        // 检查是否包含失败关键词
                        if (!str_contains($res, "couldn't find")) {
                            $factualAnswer = $res;
                            $remark = "Department Info Found";
                        }
                        break;
                }

                // -> 如果找到了知识库答案
                if ($factualAnswer) {
                    $integrationPrompt = "The user asked: '{$userMessage}'. Info found: {$factualAnswer}. Please synthesize a natural response.";
                    $naturalReply = $gemini->generateText($integrationPrompt);

                    $log = $this->logToDb($userMessage, $naturalReply, true, $remark, $logPayload);

                    return response()->json(['reply' => $naturalReply, 'log_id' => $log->id, 'status' => true]);
                }
            }

            // 🧠 情况 B: Fallback (模糊搜索 / 兜底逻辑)
            // 如果上面 Function Call 没找到结果，或者 Gemini 直接没调 Function
            \Log::info("Entering Fallback Logic for: " . $userMessage);

            $fallbackAnswer = $this->getFaqAnswerFuzzy($userMessage); // 强制使用模糊匹配再试一次

            if (is_array($fallbackAnswer)) {
                $realFallbackText = $fallbackAnswer['factual_answer'];
                $logPayload = $fallbackAnswer['log_data'];

                $integrationPrompt = "The user asked: '{$userMessage}'. Knowledge base (fuzzy match): '{$realFallbackText}'. Please rephrase naturally.";
                $naturalReply = $gemini->generateText($integrationPrompt);

                $log = $this->logToDb($userMessage, $naturalReply, true, "Success (Fuzzy Fallback)", $logPayload);

                return response()->json(['reply' => $naturalReply, 'log_id' => $log->id, 'status' => true]);
            }

            // ❌ 情况 C: 彻底失败 (知识库无匹配)
            // 即使是 AI 认为无法回答，也算作一次正常的交互，但是 status=false
            $finalFailMsg = "Sorry, I don't have information about that yet. Please ask the counter staff.";
            $log = $this->logToDb($userMessage, $finalFailMsg, false, "No matching knowledge found");

            return response()->json(['reply' => $finalFailMsg, 'log_id' => $log->id, 'status' => false]);

        } catch (\Exception $e) {
            // 🚨 情况 D: 系统/API 严重错误 (网络断了，API Key 错了等)
            \Log::error("ChatBot Exception: " . $e->getMessage());

            $errorReply = "Sorry, I am currently experiencing technical difficulties. Please try again later.";

            // 关键：这里也要记录！这样你在后台能看到系统坏了
            // 记录具体的错误信息到 remark，方便你排查
            $log = $this->logToDb($userMessage, $errorReply, false, "System Error: " . substr($e->getMessage(), 0, 200));

            return response()->json(['reply' => $errorReply, 'log_id' => $log->id, 'status' => false]);
        }
    }

    /**
     * 统一的日志记录辅助函数
     * 避免在主逻辑里写重复的 create 代码
     */
    private function logToDb($question, $answer, $status, $remark = null, $metaData = [])
    {
        // 提取第一条匹配的元数据（如果有）
        $faqId = $metaData[0]['faq_id'] ?? null;
        $intentId = $metaData[0]['intent_id'] ?? null;
        $deptId = $metaData[0]['department_id'] ?? null;

        return QuestionLog::create([
            'question_text' => $question,
            'answer_text' => $answer,
            'status' => $status,
            'checked' => $status ? true : false, // 成功默认checked，失败默认unchecked
            'remark' => $remark, // 存入失败原因或成功类型
            'faq_id' => $faqId,
            'intent_id' => $intentId,
            'department_id' => $deptId,
        ]);
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

    public function requestHumanHelp(Request $request)
    {
        $logId = $request->input('log_id');
        // 标记为请求协助
        QuestionLog::where('id', $logId)->update(['help_requested' => true]);
        return response()->json(['status' => 'success']);
    }

    public function checkAdminReply(Request $request)
    {
        $logId = $request->input('log_id');
        $log = QuestionLog::where('id', $logId)->first();

        if ($log && $log->admin_reply) {
            return response()->json([
                'replied' => true,
                'reply' => $log->admin_reply
            ]);
        }
        return response()->json(['replied' => false]);
    }

}
