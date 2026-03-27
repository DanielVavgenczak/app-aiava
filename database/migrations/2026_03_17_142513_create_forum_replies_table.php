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
        Schema::create('forum_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('topic_id')
                ->references('id')
                ->on('forum_topics')
                ->onDelete('cascade');

            // Respostas aninhadas (resposta a uma respost
            $table->uuid('parent_id')->nullable();

            $table->integer('depth')->default(0); // 0 = resposta direta, 1+ = sub-resposta

            // Conteúdo
            $table->text('content');

            // Autor (polimórfico - pode ser Professor ou Aluno)
            $table->uuid('author_id');
            // 'App\Models\Teacher\Teacher' ou 'App\Models\Teacher\Student'
            $table->string('author_type');

            // Índices para performance
            $table->index(['topic_id', 'parent_id']);
            $table->index(['author_id', 'author_type']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_replies');
    }
};
