<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attempt_id')->constrained()->cascadeOnDelete();

            // Restrict, not cascade: deleting a question must never silently rewrite
            // the contents (and therefore the score) of a historical attempt.
            $table->foreignId('question_id')->constrained()->restrictOnDelete();

            $table->unsignedTinyInteger('position');
            $table->foreignId('selected_option_id')->nullable()->constrained('options')->nullOnDelete();
            $table->boolean('is_flagged')->default(false);
            $table->timestamps();

            $table->unique(['attempt_id', 'position']);
            $table->index(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_questions');
    }
};
