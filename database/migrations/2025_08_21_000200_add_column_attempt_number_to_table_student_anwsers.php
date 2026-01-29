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
        Schema::table('student_anwsers', function (Blueprint $table) {
            $table->integer('attempt_number')->default(1)->after('corrected')
                ->comment('Número da tentativa do aluno para responder a questão');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_anwsers', function (Blueprint $table) {
            $table->dropColumn('attempt_number');
        });
    }
};
