<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CM2 exams for admission to 6e: one overall score out of 20 per student and exam.
     */
    public function up(): void
    {
        Schema::create('admission_exam_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('exam', 20);
            $table->decimal('score', 5, 2);
            $table->timestamps();

            $table->unique(['enrollment_id', 'exam']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_exam_scores');
    }
};
