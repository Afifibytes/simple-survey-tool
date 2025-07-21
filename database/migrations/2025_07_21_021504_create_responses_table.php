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
        Schema::create('responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->index();
            $table->tinyInteger('nps_score')->nullable();
            $table->text('open_text')->nullable();
            $table->text('ai_follow_up_question')->nullable();
            $table->text('ai_follow_up_answer')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['survey_id', 'created_at']);
            $table->index(['session_id', 'survey_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('responses');
    }
};
