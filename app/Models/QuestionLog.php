<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionLog extends Model
{
    public function faq()
    {
        return $this->belongsTo(Faq::class);
    }

    public function intent()
    {
        return $this->belongsTo(Intent::class);
    }

    protected $fillable = [
        'user_question',
        'matched_faq_id',
        'intent_id',
        'confidence_score',
        'asked_at',
    ];
}
