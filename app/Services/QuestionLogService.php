<?php

namespace App\Services;

use App\Models\QuestionLog;
use App\Models\Faq;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuestionLogService
{
    /**
     * 核心业务：将一条日志转化为 FAQ，并标记该日志为已处理
     * 使用事务确保原子性
     */

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

    /**
     * 批量标记为已读
     */
    public function markLogsAsChecked(array $ids)
    {
        return QuestionLog::whereIn('id', $ids)->update(['checked' => true]);
    }

    /**
     * 管理员回复人工协助请求
     */
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
