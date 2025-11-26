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

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    protected $fillable = [
        'question_text',
        'answer_text',
        'intent_id',
        'department_id',
        'status',
        'checked',
        'faq_id',
        'help_requested',
        'admin_reply',
        'replied_at'
    ];
}
