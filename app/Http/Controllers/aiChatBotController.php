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
        $gemini = new \App\Services\GeminiService();

        $functions = [
            // ... (你的 functions 定义保持不变，为了节省空间我省略了) ...
            [
                "name" => "getFaqAnswer",
                "description" => "Search for the most relevant FAQ from the database and return its answer.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "question" => ["type" => "string", "description" => "User's question about the university."]
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
                        "name" => ["type" => "string", "description" => "The name of the department."]
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

            $factualAnswer = null;
            $departmentFailureMessage = null;

            switch ($functionName) {
                case 'getFaqAnswer':
                    $question = $args['question'] ?? $userMessage;
                    $functionResult = $this->getFaqAnswer($question);
                    $logPayload = [];

                    if (is_array($functionResult)) {
                        $factualAnswer = $functionResult['factual_answer'];
                        $logPayload = $functionResult['log_data'];
                    } else {
                        $factualAnswer = $functionResult;
                    }
                    break;

                case 'getDepartmentInfo':
                    $name = $args['name'] ?? $userMessage;
                    $factualAnswer = $this->getDepartmentInfo($name);
                    $departmentFailureMessage = "I couldn't find department information for '{$name}'.";
                    break;
            }

            $failureMessage = "I couldn't find a matching FAQ for that question.";

            // ✅ 情况 A: 成功获取到信息
            if ($factualAnswer && $factualAnswer !== $failureMessage && $factualAnswer !== $departmentFailureMessage) {
                \Log::info($factualAnswer);
                $integrationPrompt = "The user asked: '{$userMessage}'.
                I have retrieved the following pieces of information from the knowledge base, separated by '---':
                {$factualAnswer}
                You are a polite chatbot for Southern University College.
                Please synthesize a single, natural, and comprehensive response by using ALL relevant facts.
                Your final answer should be ONLY the natural language response.";

                $naturalReply = $gemini->generateText($integrationPrompt);

                if (!empty($logPayload)) {
                    $mainMatch = $logPayload[0];

                    // 创建成功日志
                    $log = QuestionLog::create([
                        'question_text' => $userMessage,
                        'answer_text' => $naturalReply,
                        'faq_id' => $mainMatch['faq_id'],
                        'intent_id' => $mainMatch['intent_id'],
                        'department_id' => $mainMatch['department_id'],
                        'status' => true,
                        'checked' => true,
                    ]);

                    // 🔥 修复点 1: 返回 log_id 和 status
                    return response()->json([
                        'reply' => $naturalReply,
                        'log_id' => $log->id,
                        'status' => true
                    ]);
                }

                // 极少数情况没logPayload，但也算成功
                return response()->json(['reply' => $naturalReply, 'status' => true]);
            }

            // ❌ 情况 B: Function 调用了但没找到结果 (失败)
            if ($factualAnswer) {
                $log = QuestionLog::create([
                    'question_text' => $userMessage,
                    'status' => false, // 标记失败
                    'checked' => false,
                ]);

                // 🔥 修复点 2: 返回 log_id 和 status=false
                return response()->json([
                    'reply' => $factualAnswer,
                    'log_id' => $log->id,
                    'status' => false
                ]);
            }
        }

        // 🧠 Step 3: Fallback Logic (Fuzzy Match)
        $fallbackAnswer = $this->getFaqAnswer($userMessage);

        // ✅ 情况 C: Fuzzy Match 成功
        if ($fallbackAnswer !== "I couldn't find a matching FAQ for that question.") {
            // 这里 $fallbackAnswer 可能是数组(成功)或字符串(失败)，getFaqAnswer返回逻辑略复杂
            // 但上面的 if 既然排除了失败字符串，说明是数组结构
            // 注意：getFaqAnswer 返回的是 ['factual_answer' => ..., 'log_data' => ...]

            // 重新提取逻辑以防万一
            $realFallbackText = is_array($fallbackAnswer) ? $fallbackAnswer['factual_answer'] : $fallbackAnswer;
            $logPayload = is_array($fallbackAnswer) ? $fallbackAnswer['log_data'] : [];

            $integrationPrompt = "The user asked: '{$userMessage}'.
            The knowledge base provided: '{$realFallbackText}'.
            Please rephrase into a natural response.";

            $naturalReply = $gemini->generateText($integrationPrompt);

            if (!empty($logPayload)) {
                $mainMatch = $logPayload[0];
                $log = QuestionLog::create([
                    'question_text' => $userMessage,
                    'answer_text' => $naturalReply,
                    'faq_id' => $mainMatch['faq_id'],
                    'intent_id' => $mainMatch['intent_id'],
                    'department_id' => $mainMatch['department_id'],
                    'status' => true,
                    'checked' => true,
                ]);

                // 🔥 修复点 3: 返回完整数据
                return response()->json([
                    'reply' => $naturalReply,
                    'log_id' => $log->id,
                    'status' => true
                ]);
            }

            return response()->json(['reply' => $naturalReply, 'status' => true]);
        }

        // ❌❌ 情况 D: 彻底失败 (Step 4)
        $finalReply = is_string($response) ? $response : "Sorry, I don't have that information yet.";

        // 你之前的代码在这里没有创建 Log，导致前端没 ID 可以请求帮助
        // 🔥 修复点 4: 必须创建失败日志
        $log = QuestionLog::create([
            'question_text' => $userMessage,
            'answer_text' => $finalReply,
            'status' => false, // 标记失败
            'checked' => false
        ]);

        // 🔥 修复点 5: 返回 log_id 和 status=false
        return response()->json([
            'reply' => $finalReply,
            'log_id' => $log->id,
            'status' => false
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
