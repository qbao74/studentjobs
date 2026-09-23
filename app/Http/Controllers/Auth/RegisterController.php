<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\RegistrationService;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register', ['type' => 'student']);
    }

    public function createEmployer()
    {
        return view('auth.register', ['type' => 'employer']);
    }

    public function store(RegisterRequest $request, RegistrationService $registration)
    {
        $user = $request->isEmployer()
            ? $registration->registerEmployer($request->validated())
            : $registration->registerStudent($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json(['role' => $user->role->value, 'redirect' => url($user->role->homePath())], 201);
        }

        return redirect($user->role->homePath());
    }
}
