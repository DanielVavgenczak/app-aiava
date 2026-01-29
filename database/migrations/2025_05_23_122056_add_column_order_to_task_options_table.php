<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('task_options', function (Blueprint $table) {
            $table->unsignedInteger('order')
                ->default(0)
                ->after('task_id')
                ->comment('Ordenação das opções dentro de uma tarefa');
            // Adiciona índice para otimização
            $table->index(['task_id', 'order'], 'options_task_order_index');
            // Atualiza a ordem das opções existentes baseado no ID (opcional)
            //DB::statement('UPDATE options SET `order` = id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_options', function (Blueprint $table) {
            $table->dropIndex('options_task_order_index');

            // Remove a coluna order
            $table->dropColumn('order');
        });
    }
};
