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
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('classe_id')->references('id')->on('classes')->onDelete('cascade');
            $table->uuid('sender_id');
            $table->string('sender_type'); // Teacher ou Student
            $table->uuid('receiver_id')->nullable(); // NULL = mensagem para toda turma
            $table->string('receiver_type')->nullable(); // Teacher ou Student
            $table->text('content');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();


            $table->index(['classe_id', 'created_at']);
            $table->index(['sender_id', 'sender_type']);
            $table->index(['receiver_id', 'receiver_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
