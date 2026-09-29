<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->query('role');
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when(in_array($role, array_column(Role::cases(), 'value'), true), fn ($query) => $query->where('role', $role))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'role', 'q'));
    }

    public function show(User $user): View
    {
        $user->load(['student.skills', 'student.cv', 'employer.company']);

        return view('admin.users.show', compact('user'));
    }

    public function updateActive(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Không thể khóa chính tài khoản đang đăng nhập.']);
        }

        $active = $request->boolean('is_active');

        if ($user->hasRole(Role::Admin) && ! $active && ! $this->anotherActiveAdmin($user)) {
            return back()->withErrors(['user' => 'Phải còn ít nhất một quản trị viên đang hoạt động.']);
        }

        $user->update(['is_active' => $active]);

        return back()->with('status', $active ? "Đã mở khóa {$user->name}." : "Đã khóa {$user->name}.");
    }

    private function anotherActiveAdmin(User $user): bool
    {
        return User::query()
            ->where('role', Role::Admin)
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->exists();
    }
}
