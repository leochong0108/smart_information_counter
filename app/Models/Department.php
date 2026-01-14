<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
        protected $fillable = [
        'name',
        'description',
        'location',
        'contact_info',
    ];

    public function faqs()
    {
        return $this->hasMany(Faq::class);
    }

    public function intents()
    {
        return $this->hasMany(Intent::class);
    }

    public function questionLogs()
    {
        return $this->hasMany(QuestionLog::class);
    }

}
