<?php

namespace Tests\Feature\Student;

use App\Models\JobPost;
use App\Models\JobRecommendation;
use App\Models\Skill;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_profile_saves_fields_and_returns_new_state(): void
    {
        $student = Student::factory()->create(['bio' => null]);

        $this->actingAs($student->user)->putJson(route('api.profile.update'), [
            'name' => 'Lê Văn Bảo',
            'school' => 'ĐH Công Nghệ',
            'major' => 'Công nghệ thông tin',
            'phone' => '0901 234 567',
            'bio' => 'Sinh viên năm 3 thích backend, đang học Laravel và Docker.',
        ])->assertOk()
            ->assertJsonPath('state.user.name', 'Lê Văn Bảo')
            ->assertJsonPath('state.user.school', 'ĐH Công Nghệ');

        $this->assertSame('Lê Văn Bảo', $student->user->fresh()->name);
        $this->assertGreaterThan(0, $student->fresh()->profile_score);
    }

    public function test_invalid_phone_and_missing_name_are_rejected(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($student->user)
            ->putJson(route('api.profile.update'), ['name' => '', 'phone' => 'abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone']);
    }

    public function test_sync_skills_reuses_catalog_case_insensitively_and_creates_new(): void
    {
        $php = Skill::create(['name' => 'PHP']);
        $student = Student::factory()->create();

        $this->actingAs($student->user)
            ->putJson(route('api.profile.skills'), ['skills' => ['php', 'Docker', ' docker ']])
            ->assertOk();

        $this->assertEqualsCanonicalizing(['PHP', 'Docker'], $student->skills()->pluck('name')->all());
        $this->assertSame(2, Skill::count());
        $this->assertTrue($student->skills()->whereKey($php->id)->exists());
    }

    public function test_removing_a_skill_detaches_it_and_rescores_jobs(): void
    {
        $laravel = Skill::create(['name' => 'Laravel']);
        $job = JobPost::factory()->create();
        $job->skills()->attach($laravel->id, ['is_required' => true]);
        $student = Student::factory()->create();

        $this->actingAs($student->user)->putJson(route('api.profile.skills'), ['skills' => ['Laravel']]);
        $with = JobRecommendation::where('job_post_id', $job->id)->value('score');

        $this->actingAs($student->user)->putJson(route('api.profile.skills'), ['skills' => []])->assertOk();
        $without = JobRecommendation::where('job_post_id', $job->id)->value('score');

        $this->assertSame(0, $student->skills()->count());
        $this->assertLessThan($with, $without);
    }

    public function test_avatar_upload_validates_images(): void
    {
        Storage::fake('public');
        $student = Student::factory()->create();

        $this->actingAs($student->user)
            ->postJson(route('api.profile.avatar'), ['avatar' => UploadedFile::fake()->create('virus.php', 10, 'application/x-php')])
            ->assertJsonValidationErrors('avatar');

        $this->actingAs($student->user)
            ->postJson(route('api.profile.avatar'), ['avatar' => UploadedFile::fake()->image('me.png', 200, 200)])
            ->assertOk();

        Storage::disk('public')->assertExists($student->fresh()->avatar);
    }
}
