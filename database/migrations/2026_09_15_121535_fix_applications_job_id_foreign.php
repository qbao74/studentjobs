<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
            $table->foreign('job_id')->references('id')->on('job_posts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
        });
    }
};