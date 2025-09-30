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
        Schema::table('activit_tasks', function (Blueprint $table) {
            $table->boolean('is_avaliative')->default(false)->after('teacher_id');
            $table->decimal('total_value', 5,2)->nullable()->after('is_avaliative');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activit_tasks', function (Blueprint $table) {
            $table->dropColumn(['is_avaliative', 'total_value']);
        });
    }
};
