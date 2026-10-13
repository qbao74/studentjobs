<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Employer;
use App\Models\Payment;
use App\Models\ServicePlan;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_opens_payment_admin(): void
    {
        $this->get(route('admin.payments.index'))->assertRedirect(route('login'));
        $this->actingAs(Student::factory()->create()->user)->get(route('admin.payments.index'))->assertForbidden();
        $this->actingAs(Employer::factory()->create()->user)->get(route('admin.payments.index'))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.payments.index'))
            ->assertOk();
    }

    public function test_admin_confirms_pending_payment(): void
    {
        $employer = Employer::factory()->create([
            'company_id' => Company::factory()->withoutSubscription(),
        ]);
        $plan = ServicePlan::query()->where('code', 'basic')->firstOrFail();
        $payment = Payment::factory()->create([
            'company_id' => $employer->company_id,
            'user_id' => $employer->user_id,
            'service_plan_id' => $plan->id,
            'amount' => $plan->price_amount,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.payments.update', $payment), ['decision' => 'paid'])
            ->assertRedirect();

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $ends = $employer->company->fresh()->subscription_ends_at;
        $this->assertTrue($employer->company->fresh()->hasActiveSubscription());

        $this->actingAs($admin)
            ->patch(route('admin.payments.update', $payment), ['decision' => 'paid'])
            ->assertRedirect();

        $this->assertTrue($ends->equalTo($employer->company->fresh()->subscription_ends_at));
    }
}
