<?php

namespace Tests\Feature\Student;

use App\Models\Application;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\Skill;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CvFiles;
use Tests\TestCase;

class CvApiTest extends TestCase
{
    use RefreshDatabase;

    private function docx(string $text): UploadedFile
    {
        return new UploadedFile(CvFiles::docx($text), 'CV Lê Bảo.docx', null, null, true);
    }

    public function test_upload_returns_parsed_cv_in_state(): void
    {
        Storage::fake('local');
        Skill::create(['name' => 'Laravel']);
        $student = Student::factory()->create();

        $this->actingAs($student->user)
            ->post(route('api.profile.cv.store'), ['cv' => $this->docx('Dự án Laravel quản lý thư viện')], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('state.user.cv.name', 'CV Lê Bảo.docx')
            ->assertJsonPath('state.user.cv.status', 'parsed')
            ->assertJsonPath('state.user.cv.foundSkills', ['Laravel'])
            ->assertJsonPath('message', 'Đã tải CV và đọc được 1 kỹ năng.');
    }

    public function test_rejects_wrong_types_and_oversized_files(): void
    {
        Storage::fake('local');
        $student = Student::factory()->create();

        $this->actingAs($student->user)
            ->postJson(route('api.profile.cv.store'), ['cv' => UploadedFile::fake()->create('cv.exe', 100, 'application/x-msdownload')])
            ->assertJsonValidationErrors('cv');

        $this->actingAs($student->user)
            ->postJson(route('api.profile.cv.store'), ['cv' => UploadedFile::fake()->create('cv.pdf', 6000, 'application/pdf')])
            ->assertJsonValidationErrors('cv');
    }

    public function test_zip_renamed_to_docx_is_rejected_before_saving(): void
    {
        Storage::fake('local');
        $student = Student::factory()->create();
        $zipPath = tempnam(sys_get_temp_dir(), 'z').'.zip';
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('hello.txt', 'không phải word');
        $zip->close();

        $this->actingAs($student->user)
            ->postJson(route('api.profile.cv.store'), ['cv' => new UploadedFile($zipPath, 'cv.docx', null, null, true)])
            ->assertUnprocessable();

        $this->assertNull($student->fresh()->cv);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_download_permissions(): void
    {
        Storage::fake('local');
        $student = Student::factory()->create();
        $this->actingAs($student->user)->post(route('api.profile.cv.store'), ['cv' => $this->docx('Kinh nghiệm làm việc nhóm và thuyết trình')]);
        $cv = $student->fresh()->cv;

        $response = $this->actingAs($student->user)->get(route('cvs.download', $cv))->assertOk()->assertDownload('CV Le Bao.docx');
        $this->assertStringContainsString("filename*=utf-8''CV%20L%C3%AA%20B%E1%BA%A3o.docx", $response->headers->get('Content-Disposition'));

        $recruiter = Employer::factory()->create();
        $this->actingAs($recruiter->user)->get(route('cvs.download', $cv))->assertForbidden();

        Application::factory()->for($student)->for(JobPost::factory()->for($recruiter->company))->create();
        $this->actingAs($recruiter->user)->get(route('cvs.download', $cv))->assertOk();

        $this->actingAs(Student::factory()->create()->user)->get(route('cvs.download', $cv))->assertForbidden();
    }

    public function test_delete_cv(): void
    {
        Storage::fake('local');
        $student = Student::factory()->create();
        $this->actingAs($student->user)->post(route('api.profile.cv.store'), ['cv' => $this->docx('Thành thạo tin học văn phòng cơ bản')]);

        $this->actingAs($student->user)->deleteJson(route('api.profile.cv.destroy'))
            ->assertOk()
            ->assertJsonPath('state.user.cv', null);
    }
}
