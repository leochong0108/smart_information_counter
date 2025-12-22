<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\Department;
use Illuminate\Support\Facades\Log;

class VectorSearchService
{
    protected $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * 核心搜索入口：尝试向量搜索，失败则降级为模糊搜索
     */
    public function findRelevantFaqs(string $question): array
    {
        // 1. 尝试生成向量
        $questionEmbedding = $this->gemini->generateEmbedding($question);

        // 2. 如果向量生成失败，或者数据库里还没有向量数据，直接用模糊匹配
        if (!$questionEmbedding) {
            Log::warning("Vector generation failed for: '{$question}', falling back to fuzzy search.");
            return $this->fuzzySearch($question);
        }

        // 3. 向量搜索逻辑
        $faqs = Faq::whereNotNull('embedding')->with(['department'])->get();
        $matches = [];

        foreach ($faqs as $f) {
            $dbEmbedding = json_decode($f->embedding, true);
            if (!is_array($dbEmbedding)) continue;

            $score = $this->cosineSimilarity($questionEmbedding, $dbEmbedding);

            // 阈值设为 0.65
            if ($score > 0.65) {
                $matches[] = ['faq' => $f, 'score' => $score];
            }
        }

        // 4. 如果向量没找到结果，尝试模糊匹配作为最后防线
        if (empty($matches)) {
            Log::info("Vector search found no results for: '{$question}', trying fuzzy search.");
            return $this->fuzzySearch($question);
        }

        return $this->sortAndLimit($matches);
    }

    /**
     * 模糊搜索 (Fallback Logic)
     */
    public function fuzzySearch(string $question): array
    {
        $faqs = Faq::with(['department'])->get();
        $matches = [];

        foreach ($faqs as $f) {
            similar_text(strtolower($question), strtolower($f->question), $percent);
            // 这里 percent 是 0-100，转成 0-1 以便统一格式
            $score = $percent / 100;

            if ($percent > 60) {
                $matches[] = ['faq' => $f, 'score' => $score];
            }
        }

        return $this->sortAndLimit($matches);
    }

    /**
     * 获取部门信息
     */
    public function findDepartmentInfo(string $name): ?string
    {
        $department = Department::where('name', 'like', "%$name%")->first();

        if (!$department) {
            return null;
        }

        return "{$department->name} is located at {$department->location}. Contact: {$department->contact_info}.";
    }

    /**
     * 辅助：计算余弦相似度
     */

    private function cosineSimilarity(array $vecA, array $vecB)
    {
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        foreach ($vecA as $key => $value) {
            if (!isset($vecB[$key])) continue;
            $dotProduct += $value * $vecB[$key];
            $magnitudeA += $value ** 2;
            $magnitudeB += $vecB[$key] ** 2;
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        return ($magnitudeA * $magnitudeB) == 0 ? 0 : $dotProduct / ($magnitudeA * $magnitudeB);
    }

    /**
     * 辅助：排序并取前3
     */
    private function sortAndLimit(array $matches, int $limit = 3): array
    {
        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($matches, 0, $limit);
    }
}
