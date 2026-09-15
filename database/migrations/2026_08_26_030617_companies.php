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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // "may-creative"
            $table->string('name');
            $table->boolean('verified')->default(false);
            $table->string('tagline')->nullable();
            $table->string('size')->nullable();
            $table->string('location')->nullable();
            $table->string('color', 20)->nullable();
            $table->string('initial', 5)->nullable();
            $table->text('about')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
