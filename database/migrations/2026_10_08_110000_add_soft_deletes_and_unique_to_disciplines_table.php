<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona soft deletes em disciplines e garante unicidade de (teacher_id, title).
     *
     * Antes do índice único, duplicatas existentes são contabilizadas e logadas.
     * Se houver duplicatas reais, a criação do índice falhará de propósito
     * (é necessário corrigir os dados antes de prosseguir).
     */
    public function up(): void
    {
        $duplicadas = DB::table('disciplines')
            ->selectRaw('teacher_id, LOWER(TRIM(title)) AS titulo_normalizado, COUNT(*) AS total')
            ->groupBy('teacher_id', DB::raw('LOWER(TRIM(title))'))
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicadas->isNotEmpty()) {
            Log::warning('[disciplines] Duplicatas (teacher_id, title) encontradas antes do índice único', [
                'grupos_duplicados' => $duplicadas->count(),
                'exemplos' => $duplicadas->take(20)->all(),
            ]);
        }

        Schema::table('disciplines', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('disciplines', function (Blueprint $table) {
            $table->unique(['teacher_id', 'title'], 'disciplines_teacher_id_title_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disciplines', function (Blueprint $table) {
            $table->dropUnique('disciplines_teacher_id_title_unique');
        });

        Schema::table('disciplines', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
