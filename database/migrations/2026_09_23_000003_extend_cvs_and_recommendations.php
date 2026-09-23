<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            $table->unique('student_id'); // mỗi sinh viên giữ một CV, tải lại thì thay thế
            $table->string('mime_type', 100)->nullable()->after('path');
            $table->unsignedInteger('size')->default(0)->after('mime_type');
            $table->string('parse_status', 20)->default('pending')->after('parsed'); // pending | parsed | empty | failed
            $table->string('parse_error')->nullable()->after('parse_status');
        });

        Schema::table('job_recommendations', function (Blueprint $table) {
            $table->json('breakdown')->nullable()->after('score'); // điểm từng tiêu chí
            $table->index(['student_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::table('job_recommendations', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'score']);
            $table->dropColumn('breakdown');
        });

        Schema::table('cvs', function (Blueprint $table) {
            $table->dropUnique(['student_id']);
            $table->dropColumn(['mime_type', 'size', 'parse_status', 'parse_error']);
        });
    }
};
