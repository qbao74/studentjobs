<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->renameColumn('ten_skill', 'name');
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->unique('name');
            $table->json('aliases')->nullable()->after('name'); // ["js", "nodejs"] để nhận diện trong CV
        });

        Schema::table('students', function (Blueprint $table) {
            $table->unique('user_id');
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->unique('user_id');
            $table->string('position')->nullable()->after('company_id');
        });

        Schema::table('student_skill', function (Blueprint $table) {
            $table->string('source', 10)->default('manual')->after('skill_id'); // manual | cv
            $table->unique(['student_id', 'skill_id']);
        });

        Schema::table('job_skill', function (Blueprint $table) {
            $table->unique(['job_post_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::table('job_skill', function (Blueprint $table) {
            $table->dropUnique(['job_post_id', 'skill_id']);
        });

        Schema::table('student_skill', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'skill_id']);
            $table->dropColumn('source');
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropColumn('position');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn('aliases');
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->renameColumn('name', 'ten_skill');
        });
    }
};
