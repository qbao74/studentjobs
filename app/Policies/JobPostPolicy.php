<?php

namespace App\Policies;

use App\Enums\JobStatus;
use App\Enums\Role;
use App\Models\JobPost;
use App\Models\User;

class JobPostPolicy
{
    /** Tin đang mở ai cũng xem được; tin đóng/ẩn chỉ công ty sở hữu và admin xem. */
    public function view(?User $user, JobPost $job): bool
    {
        if ($job->status === JobStatus::Open) {
            return true;
        }

        return $user !== null && ($user->hasRole(Role::Admin) || $this->owns($user, $job));
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Employer) && $user->employer !== null;
    }

    /** Tin bị admin ẩn thì nhà tuyển dụng không tự mở lại được. */
    public function update(User $user, JobPost $job): bool
    {
        return $this->owns($user, $job) && $job->status !== JobStatus::Hidden;
    }

    public function delete(User $user, JobPost $job): bool
    {
        return $this->owns($user, $job);
    }

    public function moderate(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    private function owns(User $user, JobPost $job): bool
    {
        return $user->hasRole(Role::Employer) && $user->companyId() === $job->company_id;
    }
}
