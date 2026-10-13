<?php

namespace Tests\Feature\Billing;

use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\Payment;
use App\Models\ServicePlan;
use App\Models\Student;
use App\Services\Frontend\JoblyPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_billing(): void
    {
        $this->get(route('employer.billing.index'))->assertRedirect(route('login'));
    }

    public function test_student_cannot_open_billing(): void
    {
        $this->actingAs(Student::factory()->create()->user)
            ->get(route('employer.billing.index'))
            ->assertForbidden();
    }

    public function test_employer_creates_payment_using_plan_price_not_posted_amount(): void
    {
        $employer = Employer::factory()->create();
        $plan = ServicePlan::query()->where('code', 'basic')->firstOrFail();

        $this->actingAs($employer->user)
            ->post(route('employer.billing.store'), [
                'service_plan_id' => $plan->id,
                'amount' => 1,
            ])
            ->assertRedirect();

        $payment = Payment::query()->firstOrFail();
        $this->assertSame($plan->price_amount, $payment->amount);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertNotSame(1, $payment->amount);
    }

    public function test_simulation_success_extends_subscription_once_even_if_replayed(): void
    {
        $employer = Employer::factory()->create([
            'company_id' => Company::factory()->withoutSubscription(),
        ]);
        $plan = ServicePlan::query()->where('code', 'basic')->firstOrFail();

        $this->actingAs($employer->user)
            ->post(route('employer.billing.store'), ['service_plan_id' => $plan->id]);

        $payment = Payment::query()->firstOrFail();

        $this->actingAs($employer->user)
            ->post(route('employer.billing.simulate', $payment), ['success' => '1'])
            ->assertRedirect(route('employer.billing.show', $payment));

        $company = $employer->company->fresh();
        $this->assertTrue($company->hasActiveSubscription());
        $this->assertSame($plan->id, $company->service_plan_id);
        $ends = $company->subscription_ends_at;

        $this->actingAs($employer->user)
            ->post(route('employer.billing.simulate', $payment), ['success' => '1']);

        $this->assertTrue($ends->equalTo($employer->company->fresh()->subscription_ends_at));
        $this->assertSame(1, Payment::query()->where('status', PaymentStatus::Paid)->count());
    }

    public function test_simulation_failure_does_not_activate_plan(): void
    {
        $employer = Employer::factory()->create([
            'company_id' => Company::factory()->withoutSubscription(),
        ]);
        $plan = ServicePlan::query()->where('code', 'basic')->firstOrFail();

        $this->actingAs($employer->user)->post(route('employer.billing.store'), ['service_plan_id' => $plan->id]);
        $payment = Payment::query()->firstOrFail();

        $this->actingAs($employer->user)
            ->post(route('employer.billing.simulate', $payment), ['success' => '0']);

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertFalse($employer->company->fresh()->hasActiveSubscription());
    }

    public function test_employer_cannot_view_another_company_payment(): void
    {
        $owner = Employer::factory()->create();
        $stranger = Employer::factory()->create();
        $plan = ServicePlan::query()->where('code', 'basic')->firstOrFail();
        $payment = Payment::factory()->create([
            'company_id' => $owner->company_id,
            'user_id' => $owner->user_id,
            'service_plan_id' => $plan->id,
            'amount' => $plan->price_amount,
        ]);

        $this->actingAs($stranger->user)
            ->get(route('employer.billing.show', $payment))
            ->assertForbidden();
    }

    public function test_expired_company_jobs_are_hidden_from_students(): void
    {
        $job = JobPost::factory()->for(Company::factory()->expired())->create(['title' => 'Tin hết hạn gói']);

        $payload = app(JoblyPayload::class)->build(null);

        $this->assertNotContains('Tin hết hạn gói', array_column($payload['jobs'], 'title'));

        $student = Student::factory()->create();
        $this->actingAs($student->user)
            ->postJson(route('api.jobs.apply', $job))
            ->assertUnprocessable();
    }

    public function test_cannot_post_open_job_without_active_subscription(): void
    {
        $employer = Employer::factory()->create([
            'company_id' => Company::factory()->withoutSubscription(),
        ]);

        $this->actingAs($employer->user)
            ->post('/employer/jobs', [
                'title' => 'Backend Developer',
                'type' => 'Part-time',
                'salary' => '10–14 triệu/tháng',
                'location' => 'Quận 3, TP.HCM',
                'is_remote' => '0',
                'hours' => '4 giờ/ngày',
                'description' => 'Xây API cho ứng dụng đặt lịch.',
                'requirements' => "- Biết PHP\n- Biết SQL",
                'benefits' => 'Mentor 1:1',
                'required_skills' => 'PHP, Laravel',
            ])
            ->assertSessionHasErrors('title');
    }
}
