<?php

namespace App\Services\Billing;

use App\Enums\JobStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Payment;
use App\Models\ServicePlan;
use App\Models\User;
use App\Services\Matching\RecommendationService;
use App\Services\Notification\UserNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class BillingService
{
    public function __construct(
        private UserNotificationService $notifications,
        private RecommendationService $recommendations,
    ) {}

    public function isSimulation(): bool
    {
        return config('billing.driver', 'simulation') === 'simulation';
    }

    /**
     * Tạo yêu cầu thanh toán. Số tiền lấy từ gói trong database, không lấy từ form.
     */
    public function createRequest(User $user, Company $company, ServicePlan $plan): Payment
    {
        if (! $plan->is_active) {
            throw new UnprocessableEntityHttpException('Gói dịch vụ này không còn được bán.');
        }

        $pending = Payment::query()
            ->where('company_id', $company->id)
            ->where('status', PaymentStatus::Pending)
            ->latest()
            ->first();

        if ($pending) {
            $this->expireIfTimedOut($pending);

            if ($pending->fresh()->isPending()) {
                return $pending;
            }
        }

        return Payment::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'service_plan_id' => $plan->id,
            'amount' => $plan->price_amount,
            'currency' => config('billing.currency', 'VND'),
            'reference' => $this->nextReference(),
            'status' => PaymentStatus::Pending,
            'provider' => $this->isSimulation() ? 'simulation' : 'manual',
            'expires_at' => now()->addHours((int) config('billing.payment_expires_hours', 24)),
        ]);
    }

    /** Mô phỏng cổng thanh toán (đồ án). Không phải giao dịch ngân hàng thật. */
    public function simulate(Payment $payment, bool $success): Payment
    {
        if (! $this->isSimulation()) {
            throw new UnprocessableEntityHttpException('Chế độ mô phỏng đang tắt.');
        }

        $txn = 'sim-'.$payment->reference;

        return $success
            ? $this->markPaid($payment, $txn)
            : $this->markFailed($payment, $txn);
    }

    /**
     * Xác nhận thanh toán hợp lệ. Gọi lại cùng mã giao dịch sẽ không gia hạn lần hai.
     */
    public function markPaid(Payment $payment, string $providerTxnId): Payment
    {
        return DB::transaction(function () use ($payment, $providerTxnId) {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $this->expireIfTimedOut($locked);
            $locked->refresh();

            $sameTxn = Payment::query()->where('provider_txn_id', $providerTxnId)->first();
            if ($sameTxn && $sameTxn->id !== $locked->id) {
                throw new UnprocessableEntityHttpException('Mã giao dịch đã được dùng cho một thanh toán khác.');
            }

            if ($locked->status === PaymentStatus::Paid) {
                return $locked;
            }

            if (! $locked->isPending()) {
                throw new UnprocessableEntityHttpException('Giao dịch này không còn ở trạng thái chờ thanh toán.');
            }

            $company = Company::query()->whereKey($locked->company_id)->lockForUpdate()->firstOrFail();
            $plan = $locked->servicePlan;
            $starts = $company->hasActiveSubscription() ? $company->subscription_ends_at : now();
            $ends = $starts->copy()->addDays($plan->duration_days);

            $locked->update([
                'status' => PaymentStatus::Paid,
                'provider_txn_id' => $providerTxnId,
                'paid_at' => now(),
                'starts_on' => $starts,
                'ends_on' => $ends,
            ]);

            $company->update([
                'service_plan_id' => $plan->id,
                'subscription_starts_at' => $company->subscription_starts_at ?? $starts,
                'subscription_ends_at' => $ends,
            ]);

            $this->refreshOpenJobs($company->fresh());
            $this->notifyCompany($company, $locked->fresh(), 'Thanh toán thành công', 'Gói '.$plan->name.' đã được kích hoạt đến '.$ends->format('d/m/Y').'.');

            return $locked->fresh();
        });
    }

    public function markFailed(Payment $payment, ?string $providerTxnId = null): Payment
    {
        return DB::transaction(function () use ($payment, $providerTxnId) {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $this->expireIfTimedOut($locked);
            $locked->refresh();

            if ($locked->status === PaymentStatus::Paid) {
                throw new UnprocessableEntityHttpException('Giao dịch đã thành công, không thể đổi thành thất bại.');
            }

            if ($locked->status === PaymentStatus::Failed) {
                return $locked;
            }

            if (! $locked->isPending()) {
                throw new UnprocessableEntityHttpException('Giao dịch này không còn ở trạng thái chờ thanh toán.');
            }

            $locked->update([
                'status' => PaymentStatus::Failed,
                'provider_txn_id' => $providerTxnId,
                'failed_at' => now(),
            ]);

            $this->notifyCompany($locked->company, $locked->fresh(), 'Thanh toán thất bại', 'Giao dịch '.$locked->reference.' không thành công. Tin tuyển dụng chưa được gia hạn.');

            return $locked->fresh();
        });
    }

    public function expireIfTimedOut(Payment $payment): void
    {
        if (! $payment->hasTimedOut()) {
            return;
        }

        $payment->update(['status' => PaymentStatus::Expired]);
    }

    /** Tin hết hạn gói không còn hiện với sinh viên; tính lại điểm khớp. */
    public function refreshOpenJobs(Company $company): void
    {
        $company->jobPosts()->where('status', JobStatus::Open)->each(function ($job): void {
            $this->recommendations->refreshForJob($job);
        });
    }

    private function notifyCompany(Company $company, Payment $payment, string $title, string $body): void
    {
        $company->loadMissing('employers.user');

        foreach ($company->employers as $employer) {
            $this->notifications->notify(
                $employer->user,
                NotificationType::PaymentUpdated,
                'payment:'.$payment->id.':'.$payment->status->value,
                [
                    'title' => $title,
                    'body' => $body,
                    'link' => route('employer.billing.show', $payment),
                ],
                $payment,
            );
        }
    }

    private function nextReference(): string
    {
        do {
            $reference = 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Payment::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
