<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Intent extends Model
{
    protected $fillable = [
        'intent_name',
        'description',
        'department_id',
    ];

    // One Intent can have many FAQs
    public function faqs()
    {
        return $this->hasMany(Faq::class);
    }

    public function questionLogs()
    {
        return $this->hasMany(QuestionLog::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }



}
