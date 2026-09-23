<?php

namespace App\Services\Student;

use App\Models\Skill;
use App\Models\Student;
use App\Services\Profile\ProfileRefresher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileService
{
    public function __construct(private ProfileRefresher $refresher) {}

    /**
     * @param  array{name: string, school?: ?string, major?: ?string, year?: ?string, location?: ?string, phone?: ?string, bio?: ?string}  $data
     */
    public function update(Student $student, array $data): void
    {
        DB::transaction(function () use ($student, $data) {
            $student->user->update(['name' => $data['name']]);
            $student->update(collect($data)->only(['school', 'major', 'year', 'location', 'phone', 'bio'])->all());
        });

        $this->refresher->refresh($student);
    }

    /**
     * Danh sách gửi lên là toàn bộ kỹ năng mong muốn.
     * Kỹ năng mới chưa có trong danh mục sẽ được tạo; kỹ năng đọc từ CV mà vẫn giữ thì giữ nguyên nguồn "cv".
     *
     * @param  list<string>  $names
     */
    public function syncSkills(Student $student, array $names): void
    {
        $ids = collect($names)
            ->map(fn (string $n) => Str::squish($n))
            ->filter()
            ->unique(fn (string $n) => Str::lower($n))
            ->map(fn (string $n) => $this->findOrCreateSkill($n)->id)
            ->values();

        DB::transaction(function () use ($student, $ids) {
            $student->skills()->detach(
                $student->skills()->pluck('skills.id')->diff($ids)->all()
            );

            $existing = $student->skills()->pluck('skills.id');
            $student->skills()->attach(
                array_fill_keys($ids->diff($existing)->all(), ['source' => 'manual'])
            );
        });

        $this->refresher->refresh($student);
    }

    public function updateAvatar(Student $student, UploadedFile $file): string
    {
        $path = $file->store('avatars', 'public');
        $old = $student->avatar;

        $student->update(['avatar' => $path]);

        if ($old && ! Str::startsWith($old, ['http://', 'https://'])) {
            Storage::disk('public')->delete($old);
        }

        return $path;
    }

    /** So tên không phân biệt hoa thường: "php" dùng lại kỹ năng "PHP" đã có. */
    private function findOrCreateSkill(string $name): Skill
    {
        return Skill::whereRaw('LOWER(name) = ?', [Str::lower($name)])->first()
            ?? Skill::create(['name' => $name, 'aliases' => []]);
    }
}
