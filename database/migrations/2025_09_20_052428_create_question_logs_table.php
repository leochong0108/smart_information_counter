<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('question_logs', function (Blueprint $table) {
        $table->id();
        $table->text('question_text');
        $table->text('answer_text')->nullable();
        $table->foreignId('intent_id')->nullable()->constrained('intents')->onDelete('set null');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_logs');
    }
};
