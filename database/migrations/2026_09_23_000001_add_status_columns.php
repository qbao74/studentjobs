<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
            $table->index('role');
        });

        Schema::table('job_posts', function (Blueprint $table) {
            $table->string('status', 20)->default('open')->after('benefits'); // open | closed | hidden
            $table->boolean('is_remote')->default(false)->after('location');
            $table->index(['status', 'created_at']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->text('note')->nullable()->after('status');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('note');
        });

        Schema::table('job_posts', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['status', 'is_remote']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('is_active');
        });
    }
};
