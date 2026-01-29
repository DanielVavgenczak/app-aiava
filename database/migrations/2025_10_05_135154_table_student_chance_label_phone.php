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
            $table->dropUnique(['phone']);
        });
        DB::statement('CREATE UNIQUE INDEX students_phone_non_null_unique ON students (phone) WHERE phone IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX students_phone_non_null_unique');
        Schema::table('students', function (Blueprint $table) {
            //
            $table->unique('phone');
        });
    }
};
