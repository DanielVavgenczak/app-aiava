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
        Schema::table('students', function (Blueprint $table) {
          $table->unsignedInteger('position')->after('id')->nullable();
        });
          // Atualiza os registros existentes com valores sequenciais
        $students = DB::table('students')
                     ->orderBy('created_at', 'asc')
                     ->get();

        $position = 1;
        foreach ($students as $student) {
            DB::table('students')
                ->where('id', $student->id)
                ->update(['position' => $position++]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
