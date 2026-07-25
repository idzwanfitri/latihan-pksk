<?php

use App\Enums\Section;
use App\Enums\Subject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->enum('section', Section::values());
            $table->enum('subject', Subject::values())->nullable();
            $table->text('question_text');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['section', 'is_active']);
            $table->index(['section', 'subject', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
