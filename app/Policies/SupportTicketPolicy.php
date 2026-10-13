<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Student, Role::Employer, Role::Admin);
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->hasRole(Role::Admin) || $ticket->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Student, Role::Employer);
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        if ($ticket->isClosed()) {
            return false;
        }

        return $user->hasRole(Role::Admin) || $ticket->user_id === $user->id;
    }

    public function close(User $user, SupportTicket $ticket): bool
    {
        return $user->hasRole(Role::Admin) || $ticket->user_id === $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }
}
