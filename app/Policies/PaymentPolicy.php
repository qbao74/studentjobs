<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Employer, Role::Admin);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasRole(Role::Admin) || $this->ownsCompany($user, $payment->company_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Employer) && $user->employer !== null;
    }

    public function simulate(User $user, Payment $payment): bool
    {
        return $this->ownsCompany($user, $payment->company_id);
    }

    public function manage(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    private function ownsCompany(User $user, int $companyId): bool
    {
        return $user->hasRole(Role::Employer) && $user->companyId() === $companyId;
    }
}
