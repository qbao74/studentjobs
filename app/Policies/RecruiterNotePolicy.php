<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Application;
use App\Models\RecruiterNote;
use App\Models\User;

class RecruiterNotePolicy
{
    public function view(User $user, RecruiterNote $note): bool
    {
        return $this->owns($user, $note);
    }

    public function create(User $user, Application $application): bool
    {
        return $this->recruits($user, $application);
    }

    public function update(User $user, RecruiterNote $note): bool
    {
        return $this->owns($user, $note);
    }

    public function delete(User $user, RecruiterNote $note): bool
    {
        return $this->owns($user, $note);
    }

    private function owns(User $user, RecruiterNote $note): bool
    {
        return $user->hasRole(Role::Employer) && $user->employer?->id === $note->employer_id;
    }

    private function recruits(User $user, Application $application): bool
    {
        return $user->hasRole(Role::Employer)
            && $user->companyId() !== null
            && $user->companyId() === $application->jobPost->company_id;
    }
}
