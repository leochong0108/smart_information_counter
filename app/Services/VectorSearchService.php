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

    public function findRelevantFaqs(string $question): array
    {
        $questionEmbedding = $this->gemini->generateEmbedding($question);

        if (!$questionEmbedding) {
            Log::warning("Vector generation failed for: '{$question}', falling back to fuzzy search.");
            return $this->fuzzySearch($question);
        }

        $faqs = Faq::whereNotNull('embedding')->with(['department'])->get();
        $matches = [];

        foreach ($faqs as $f) {
            $dbEmbedding = json_decode($f->embedding, true);
            if (!is_array($dbEmbedding)) continue;

            $score = $this->cosineSimilarity($questionEmbedding, $dbEmbedding);

            if ($score > 0.65) {
                $matches[] = ['faq' => $f, 'score' => $score];
            }
        }

        if (empty($matches)) {
            Log::info("Vector search found no results for: '{$question}', trying fuzzy search.");
            return $this->fuzzySearch($question);
        }

        return $this->sortAndLimit($matches);
    }


    public function fuzzySearch(string $question): array
    {
        $faqs = Faq::with(['department'])->get();
        $matches = [];

        foreach ($faqs as $f) {
            similar_text(strtolower($question), strtolower($f->question), $percent);

            $score = $percent / 100;

            if ($percent > 60) {
                $matches[] = ['faq' => $f, 'score' => $score];
            }
        }

        return $this->sortAndLimit($matches);
    }

    public function findDepartmentInfo(string $name): ?string
    {
        $department = Department::where('name', 'like', "%$name%")->first();

        if (!$department) {
            return null;
        }

        return "{$department->name} is located at {$department->location}. Contact: {$department->contact_info}.";
    }


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

    private function sortAndLimit(array $matches, int $limit = 3): array
    {
        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($matches, 0, $limit);
    }
}
