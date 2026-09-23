<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Cv;
use App\Models\User;

class CvPolicy
{
    /**
     * Tải file CV: chủ CV, admin, hoặc HR của công ty mà sinh viên đã nộp đơn.
     * HR không xem được CV của sinh viên chưa ứng tuyển vào công ty mình.
     */
    public function download(User $user, Cv $cv): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        if ($user->hasRole(Role::Student)) {
            return $user->student?->id === $cv->student_id;
        }

        $companyId = $user->companyId();

        return $companyId !== null && $cv->student->applications()
            ->whereHas('jobPost', fn ($q) => $q->where('company_id', $companyId))
            ->exists();
    }
}
