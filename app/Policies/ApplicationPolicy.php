<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    /** Sinh viên nộp đơn, HR của công ty đăng tin, và admin. */
    public function view(User $user, Application $application): bool
    {
        return $user->hasRole(Role::Admin)
            || $this->isApplicant($user, $application)
            || $this->isRecruiter($user, $application);
    }

    public function updateStatus(User $user, Application $application): bool
    {
        return $this->isRecruiter($user, $application) && ! $application->status->isFinal();
    }

    /** Nhắn tin: chỉ hai bên của đơn, admin không chen vào hội thoại. */
    public function message(User $user, Application $application): bool
    {
        return $this->isApplicant($user, $application) || $this->isRecruiter($user, $application);
    }

    public function withdraw(User $user, Application $application): bool
    {
        return $this->isApplicant($user, $application) && ! $application->status->isFinal();
    }

    private function isApplicant(User $user, Application $application): bool
    {
        return $user->hasRole(Role::Student) && $user->student?->id === $application->student_id;
    }

    private function isRecruiter(User $user, Application $application): bool
    {
        return $user->hasRole(Role::Employer)
            && $user->companyId() !== null
            && $user->companyId() === $application->jobPost->company_id;
    }
}
