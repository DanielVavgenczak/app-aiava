<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Garante que um aluno tenha no máximo uma resposta por questão por tentativa.
     *
     * Antes do índice único, duplicatas existentes são contabilizadas e logadas.
     * Se houver duplicatas reais, a criação do índice falhará de propósito
     * (é necessário corrigir os dados antes de prosseguir).
     */
    public function up(): void
    {
        $duplicadas = DB::table('student_anwsers')
            ->selectRaw('task_id, student_id, attempt_number, COUNT(*) AS total')
            ->groupBy('task_id', 'student_id', 'attempt_number')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicadas->isNotEmpty()) {
            Log::warning('[student_anwsers] Duplicatas (task_id, student_id, attempt_number) encontradas antes do índice único', [
                'grupos_duplicados' => $duplicadas->count(),
                'exemplos' => $duplicadas->take(20)->all(),
            ]);
        }

        Schema::table('student_anwsers', function (Blueprint $table) {
            $table->unique(
                ['task_id', 'student_id', 'attempt_number'],
                'student_anwsers_task_student_attempt_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_anwsers', function (Blueprint $table) {
            $table->dropUnique('student_anwsers_task_student_attempt_unique');
        });
    }
};
