<?php

namespace App\Http\Controllers\Employer;

use App\Enums\JobStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employer\CreatePaymentRequest;
use App\Models\Payment;
use App\Models\ServicePlan;
use App\Services\Billing\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class BillingController extends Controller
{
    public function __construct(private BillingService $billing) {}

    public function index(Request $request): View
    {
        $company = $request->user()->employer->company()->with('servicePlan')->first();
        $this->expirePending($company->id);

        $jobs = $company->jobPosts()->latest()->get();
        $state = $company->subscriptionState();

        $jobs->each(function ($job) use ($state): void {
            $job->setAttribute('listing_state', match (true) {
                $job->status !== JobStatus::Open => $job->status->value,
                $state === 'expired', $state === 'none' => 'expired',
                $state === 'expiring' => 'expiring',
                default => 'active',
            });
        });

        return view('employer.billing.index', [
            'company' => $company,
            'plans' => ServicePlan::query()->active()->orderBy('price_amount')->get(),
            'payments' => $company->payments()->with('servicePlan')->latest()->paginate(10),
            'jobs' => $jobs,
            'simulation' => $this->billing->isSimulation(),
        ]);
    }

    public function store(CreatePaymentRequest $request): RedirectResponse
    {
        $plan = ServicePlan::query()->findOrFail($request->integer('service_plan_id'));
        $company = $request->user()->employer->company;

        try {
            $payment = $this->billing->createRequest($request->user(), $company, $plan);
        } catch (UnprocessableEntityHttpException $e) {
            return back()->withErrors(['service_plan_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('employer.billing.show', $payment)
            ->with('status', $payment->wasRecentlyCreated
                ? 'Đã tạo yêu cầu thanh toán. Số tiền lấy từ gói trên hệ thống, không lấy từ form.'
                : 'Bạn đang có một giao dịch chờ thanh toán. Hãy hoàn tất hoặc đợi hết hạn trước khi tạo yêu cầu mới.');
    }

    public function show(Request $request, Payment $payment): View
    {
        Gate::authorize('view', $payment);
        $this->billing->expireIfTimedOut($payment);

        return view('employer.billing.show', [
            'payment' => $payment->fresh()->load(['servicePlan', 'company']),
            'simulation' => $this->billing->isSimulation(),
        ]);
    }

    public function simulate(Request $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('simulate', $payment);

        $success = $request->boolean('success');

        try {
            $payment = $success
                ? $this->billing->simulate($payment, true)
                : $this->billing->simulate($payment, false);
        } catch (UnprocessableEntityHttpException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()
            ->route('employer.billing.show', $payment)
            ->with('status', $success
                ? 'Mô phỏng thành công: gói đã được kích hoạt. Đây không phải giao dịch tiền thật.'
                : 'Mô phỏng thất bại: gói chưa được gia hạn.');
    }

    private function expirePending(int $companyId): void
    {
        Payment::query()
            ->where('company_id', $companyId)
            ->where('status', PaymentStatus::Pending)
            ->get()
            ->each(fn (Payment $payment) => $this->billing->expireIfTimedOut($payment));
    }
}
