<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
        protected $fillable = [
        'question',
        'answer',
        'intent_id',
        'department_id',
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
}
