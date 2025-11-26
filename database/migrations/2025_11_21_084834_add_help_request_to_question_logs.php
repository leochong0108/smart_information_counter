<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('question_logs', function (Blueprint $table) {
            $table->boolean('help_requested')->default(false); // Kiosk: 用户按了按钮
            $table->text('admin_reply')->nullable();           // Admin: 管理员回复的内容
            $table->timestamp('replied_at')->nullable();       // Admin: 回复时间
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_logs', function (Blueprint $table) {
            //
        });
    }
};
