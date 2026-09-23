<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_post_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->json('pros')->nullable();
            $table->json('cons')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'job_post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_recommendations');
    }
};
