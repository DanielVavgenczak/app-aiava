<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nota atribuída pelo professor à resposta do aluno.
     */
    public function up(): void
    {
        Schema::table('student_anwsers', function (Blueprint $table) {
            $table->decimal('grade', 5, 2)->nullable()->after('is_correct');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('student_anwsers', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }
};
