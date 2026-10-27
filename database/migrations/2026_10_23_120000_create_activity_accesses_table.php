<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Primeiro acesso do aluno a uma atividade.
     *
     * O sistema não possuía registro de acesso; esta tabela preenche essa
     * lacuna para permitir distinguir "não acessada" de "pendente".
     */
    public function up(): void
    {
        Schema::create('activity_accesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activit_task_id')
                ->constrained('activit_tasks')
                ->onDelete('cascade');
            $table->foreignUuid('student_id')
                ->constrained('students')
                ->onDelete('cascade');
            $table->dateTime('first_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['activit_task_id', 'student_id'],
                'activity_accesses_activity_student_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_accesses');
    }
};
