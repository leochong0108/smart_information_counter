<?php

namespace App\Services;

use App\Models\QuestionLog;
use App\Models\Faq;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuestionLogService
{

    public function convertLogToFaq(int $logId, array $faqData)
    {
        return DB::transaction(function () use ($logId, $faqData) {

            $log = QuestionLog::findOrFail($logId);

            $faq = Faq::create($faqData);

            $log->update([
                'checked' => true,
                'faq_id' => $faq->id,
            ]);

            return [
                'faq' => $faq,
                'log' => $log
            ];
        });
    }

    public function markLogsAsChecked(array $ids)
    {
        return QuestionLog::whereIn('id', $ids)->update(['checked' => true]);
    }

    public function replyToRequest(int $logId, string $replyText)
    {
        $log = QuestionLog::find($logId);

        if ($log) {
            $log->update([
                'admin_reply' => $replyText,
                'replied_at' => now()
            ]);
            return true;
        }

        return false;
    }
}
