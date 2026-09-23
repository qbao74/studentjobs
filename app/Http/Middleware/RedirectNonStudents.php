<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Trang dành cho sinh viên/khách: nhà tuyển dụng và admin được đưa về khu của mình. */
class RedirectNonStudents
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasRole(Role::Student)) {
            return redirect($user->role->homePath());
        }

        return $next($request);
    }
}
