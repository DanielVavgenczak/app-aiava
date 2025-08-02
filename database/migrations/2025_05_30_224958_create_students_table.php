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
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique()->nullable();
            $table->foreignUuid('teacher_id')->constrained('teachers')->onDelete('cascade')->nullable();
            $table->foreignUuid('classe_id')->constrained('classes')->onDelete('cascade');
            $table->timestamps();
        });
         Schema::create('password_reset_tokens_students', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Schema::create('sessions_students', function (Blueprint $table) {
        //     $table->string('id')->primary();
        //     $table->foreignUuid('student_id')->nullable()->index();
        //     $table->string('ip_address', 45)->nullable();
        //     $table->text('user_agent')->nullable();
        //     $table->longText('payload');
        //     $table->integer('last_activity')->index();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
        Schema::dropIfExists('password_reset_tokens_students');
       // Schema::dropIfExists('sessions_students');
    }
    
};
