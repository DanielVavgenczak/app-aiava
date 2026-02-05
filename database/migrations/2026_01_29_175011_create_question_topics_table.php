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
        Schema::create('question_topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('forum_topic_id')
                ->constrained('forum_topics')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->text('content');
            $table->foreignUuid('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->onDelete('cascade');

            $table->foreignUuid('student_id')
                ->nullable()
                ->constrained('students')
                ->onDelete('cascade');
            $table->timestamps();
        });

        DB::statement('
                ALTER TABLE question_topics
                ADD CONSTRAINT author_required
                CHECK (student_id IS NOT NULL OR teacher_id IS NOT NULL)
            ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_topics');
    }
};
