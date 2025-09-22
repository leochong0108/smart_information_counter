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
    Schema::create('intents', function (Blueprint $table) {
        $table->id();
        $table->string('intent_name');
        $table->text('description')->nullable();
        $table->text('example_question')->nullable();
        $table->string('function_name')->nullable();
        $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intents');
    }
};
