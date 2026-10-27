<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nota final atribuída pelo professor à atividade de um aluno.
     */
    public function up(): void
    {
        Schema::create('activity_grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activit_task_id')
                ->constrained('activit_tasks')
                ->onDelete('cascade');
            $table->foreignUuid('student_id')
                ->constrained('students')
                ->onDelete('cascade');
            $table->foreignUuid('teacher_id')
                ->constrained('teachers')
                ->onDelete('cascade');
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->dateTime('registered_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['activit_task_id', 'student_id'],
                'activity_grades_activity_student_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_grades');
    }
};
