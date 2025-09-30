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
        Schema::create('question_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('feedback')->nullable();
            $table->foreignUuid('student_anwser_id')
                ->constrained('student_anwsers')
                ->onDelete('cascade');

            $table->foreignUuid('task_id')
                ->constrained('tasks')
                ->onDelete('cascade');

            $table->foreignUuid('student_id')
                ->constrained('students')
                ->onDelete('cascade');

            $table->foreignUuid('teacher_id')
                ->constrained('teachers')
                ->onDelete('cascade');
            $table->dateTime('registered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_feedback');
    }
};
