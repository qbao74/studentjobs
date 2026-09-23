<?php

namespace Tests\Feature\Auth;

use App\Models\Employer;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_logs_in_and_goes_home(): void
    {
        $student = Student::factory()->create();

        $this->post(route('login'), ['email' => $student->user->email, 'password' => 'password'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($student->user);
    }

    public function test_employer_is_redirected_to_employer_area(): void
    {
        $employer = Employer::factory()->create();

        $this->post(route('login'), ['email' => $employer->user->email, 'password' => 'password'])
            ->assertRedirect('/employer');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'sai-mat-khau'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_locked_account_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.']);

        $this->assertGuest();
    }

    public function test_locked_user_is_logged_out_on_next_request(): void
    {
        $student = Student::factory()->create();
        $this->actingAs($student->user);

        $student->user->update(['is_active' => false]);

        $this->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_too_many_failed_attempts_are_throttled(): void
    {
        $user = User::factory()->create();
        RateLimiter::clear(strtolower($user->email).'|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'sai']);
        }

        $response = $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('quá nhiều lần', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_logout_ends_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_popup_login_gets_json_instead_of_redirect(): void
    {
        $student = Student::factory()->create();

        $this->postJson(route('login'), ['email' => $student->user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJson(['role' => 'student', 'redirect' => url('/')]);
    }

    public function test_popup_login_errors_are_json(): void
    {
        $this->postJson(route('login'), ['email' => 'x@y.vn', 'password' => 'sai'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
