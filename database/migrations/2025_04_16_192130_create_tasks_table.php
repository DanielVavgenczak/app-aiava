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
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('question');
            $table->enum('type', ['multiple_choice', 'dissertative']);

            $table->foreignUuid('module_id')->constrained('modules')->onDelete('cascade');
            $table->foreignUuid('discipline_id')->constrained('disciplines')->onDelete('cascade');
            $table->foreignUuid('teacher_id')->constrained(table: 'teachers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
