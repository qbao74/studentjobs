<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $home = $request->user()->role->homePath();

        // Popup đăng nhập gửi JSON: trả về vai trò để JS tự quyết định tải lại hay chuyển trang.
        if ($request->expectsJson()) {
            return response()->json(['role' => $request->user()->role->value, 'redirect' => url($home)]);
        }

        return redirect()->intended($home);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
