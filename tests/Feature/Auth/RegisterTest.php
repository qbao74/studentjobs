<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_registration_creates_user_and_profile(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Nguyễn An',
            'email' => 'An@Example.com',
            'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123',
        ])->assertRedirect('/');

        $user = User::where('email', 'an@example.com')->firstOrFail();
        $this->assertSame(Role::Student, $user->role);
        $this->assertNotNull($user->student);
        $this->assertAuthenticatedAs($user);
    }

    public function test_employer_registration_creates_company(): void
    {
        $this->post(route('register.employer.store'), [
            'name' => 'Trần Hà',
            'email' => 'ha@congty.vn',
            'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123',
            'company_name' => 'Công ty Ánh Dương',
            'position' => 'HR',
        ])->assertRedirect('/employer');

        $user = User::where('email', 'ha@congty.vn')->firstOrFail();
        $this->assertSame(Role::Employer, $user->role);
        $this->assertSame('cong-ty-anh-duong', $user->employer->company->slug);
        $this->assertFalse($user->employer->company->verified);
    }

    public function test_duplicate_company_names_get_unique_slugs(): void
    {
        Company::factory()->create(['slug' => 'anh-duong']);

        $this->post(route('register.employer.store'), [
            'name' => 'Hà', 'email' => 'x@y.vn', 'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123', 'company_name' => 'Ánh Dương',
        ]);

        $this->assertDatabaseHas('companies', ['slug' => 'anh-duong-2']);
    }

    public function test_employer_registration_requires_company_name(): void
    {
        $this->post(route('register.employer.store'), [
            'name' => 'Hà', 'email' => 'x@y.vn', 'password' => 'matkhau123', 'password_confirmation' => 'matkhau123',
        ])->assertSessionHasErrors('company_name');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_role_cannot_be_injected_through_the_form(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Hacker', 'email' => 'h@x.vn', 'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123', 'role' => 'admin',
        ]);

        $this->assertSame(Role::Student, User::where('email', 'h@x.vn')->first()->role);
    }

    public function test_duplicate_email_and_weak_password_are_rejected(): void
    {
        User::factory()->create(['email' => 'a@b.vn']);

        $this->post(route('register.store'), [
            'name' => 'A', 'email' => 'a@b.vn', 'password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh',
        ])->assertSessionHasErrors(['email', 'password']);
    }
}
