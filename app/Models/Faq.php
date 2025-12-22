<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\GeminiService;

class Faq extends Model
{
        protected $fillable = [
        'question',
        'answer',
        'intent_id',
        'department_id',
        'embedding'
    ];

    // FAQ belongs to one Intent
    public function intent()
    {
        return $this->belongsTo(Intent::class);
    }

    // FAQ belongs to one Department
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function questionLogs()
    {
        return $this->hasMany(QuestionLog::class);
    }

    // ⚡️ 自动化逻辑：当 FAQ 创建或更新时，自动生成向量

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($faq) {

            if ($faq->isDirty('question') || empty($faq->embedding)) {
                $gemini = new GeminiService();
                $vector = $gemini->generateEmbedding($faq->question);

                if ($vector) {
                    $faq->embedding = json_encode($vector);
                }
            }
        });
    }
}
