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
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['module_id']);
            $table->dropForeign(['discipline_id']);
            $table->dropForeign(['teacher_id']);

            $table->dropColumn(['module_id', 'discipline_id', 'teacher_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignUuid('module_id')->nullable()->constrained('modules')->onDelete('cascade');
            $table->foreignUuid('discipline_id')->nullable()->constrained('disciplines')->onDelete('cascade');
            $table->foreignUuid('teacher_id')->nullable()->constrained('users')->onDelete('cascade');
        });
    }
};
