<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CompanyReview;
use App\Models\User;

class CompanyReviewPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole(Role::Student) && $user->student !== null;
    }

    public function update(User $user, CompanyReview $review): bool
    {
        return $this->owns($user, $review);
    }

    public function delete(User $user, CompanyReview $review): bool
    {
        return $this->owns($user, $review);
    }

    private function owns(User $user, CompanyReview $review): bool
    {
        return $user->hasRole(Role::Student) && $user->student?->id === $review->student_id;
    }
}
