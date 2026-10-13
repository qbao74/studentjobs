<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_amount');
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedSmallInteger('max_open_jobs')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('service_plan_id')->nullable()->after('reviews_count')->constrained('service_plans')->nullOnDelete();
            $table->timestamp('subscription_starts_at')->nullable()->after('service_plan_id');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_starts_at');
            $table->index('subscription_ends_at');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_plan_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->string('currency', 8)->default('VND');
            $table->string('reference', 40)->unique();
            $table->string('status', 20)->default('pending');
            $table->string('provider', 40)->default('simulation');
            $table->string('provider_txn_id')->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('starts_on')->nullable();
            $table->timestamp('ends_on')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('company_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->timestamps();
            $table->unique(['student_id', 'company_id']);
            $table->index('company_id');
        });

        Schema::create('recruiter_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['application_id', 'employer_id']);
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('title');
            $table->text('body');
            $table->string('link')->nullable();
            $table->string('dedup_key')->unique();
            $table->nullableMorphs('related');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 30);
            $table->string('subject');
            $table->string('status', 20)->default('open');
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('status');
        });

        Schema::create('support_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        $now = now();
        foreach (config('billing.plans') as $plan) {
            DB::table('service_plans')->insert([
                'code' => $plan['code'],
                'name' => $plan['name'],
                'description' => $plan['description'],
                'price_amount' => $plan['price_amount'],
                'duration_days' => $plan['duration_days'],
                'max_open_jobs' => $plan['max_open_jobs'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $standardId = DB::table('service_plans')->where('code', 'standard')->value('id');
        DB::table('companies')->whereNull('subscription_ends_at')->update([
            'service_plan_id' => $standardId,
            'subscription_starts_at' => $now,
            'subscription_ends_at' => $now->copy()->addDays(30),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('support_replies');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('recruiter_notes');
        Schema::dropIfExists('company_reviews');
        Schema::dropIfExists('payments');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_plan_id');
            $table->dropIndex(['subscription_ends_at']);
            $table->dropColumn(['subscription_starts_at', 'subscription_ends_at']);
        });

        Schema::dropIfExists('service_plans');
    }
};
