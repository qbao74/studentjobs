<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Billing\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PaymentController extends Controller
{
    public function __construct(private BillingService $billing) {}

    public function index(Request $request): View
    {
        Gate::authorize('manage', Payment::class);

        $status = $request->query('status');

        $payments = Payment::query()
            ->with(['company', 'servicePlan', 'user'])
            ->when(in_array($status, PaymentStatus::values(), true), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', compact('payments', 'status'));
    }

    public function show(Payment $payment): View
    {
        Gate::authorize('view', $payment);

        $this->billing->expireIfTimedOut($payment);

        return view('admin.payments.show', [
            'payment' => $payment->fresh()->load(['company', 'servicePlan', 'user']),
        ]);
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('manage', Payment::class);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['paid', 'failed'])],
        ]);

        try {
            $payment = $data['decision'] === 'paid'
                ? $this->billing->markPaid($payment, 'admin-'.$payment->reference)
                : $this->billing->markFailed($payment, 'admin-fail-'.$payment->reference);
        } catch (UnprocessableEntityHttpException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('status', 'Đã cập nhật giao dịch '.$payment->reference.' thành '.$payment->status->label().'.');
    }
}
