@extends('layout.panel')
@php
    $editing = $job->exists;
    $skillsOf = fn (bool $required) => $job->skills->where('pivot.is_required', $required)->pluck('name')->implode(', ');
@endphp
@section('title', $editing ? 'Sửa tin' : 'Đăng tin mới')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">{{ $editing ? 'Sửa tin tuyển dụng' : 'Đăng tin mới' }}</h1>
            <p class="page-sub">Kỹ năng và mô tả càng rõ, AI càng xếp đúng ứng viên phù hợp lên đầu.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('employer.jobs.index') }}"><i data-lucide="arrow-left"></i> Danh sách tin</a>
    </header>

    <form method="POST" action="{{ $editing ? route('employer.jobs.update', $job) : route('employer.jobs.store') }}" class="card section panel-form" novalidate>
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="form-grid">
            <label class="form-field form-field--wide">Tên vị trí *
                <input type="text" name="title" value="{{ old('title', $job->title) }}" required maxlength="150" placeholder="VD: Frontend Developer (Part-time)">
                @error('title') <span class="form-error">{{ $message }}</span> @enderror
            </label>

            <label class="form-field">Hình thức *
                <select name="type" required>
                    @foreach (\App\Http\Requests\Employer\JobPostRequest::TYPES as $type)
                        <option value="{{ $type }}" @selected(old('type', $job->type) === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                @error('type') <span class="form-error">{{ $message }}</span> @enderror
            </label>

            <label class="form-field">Mức lương
                <input type="text" name="salary" value="{{ old('salary', $job->salary) }}" maxlength="100" placeholder="VD: 8–12 triệu/tháng (để trống = Thỏa thuận)">
                @error('salary') <span class="form-error">{{ $message }}</span> @enderror
            </label>

            <label class="form-field">Địa điểm
                <input type="text" name="location" value="{{ old('location', $job->is_remote && $job->location === 'Remote' ? '' : $job->location) }}" maxlength="150" placeholder="VD: Quận 1, TP.HCM">
                @error('location') <span class="form-error">{{ $message }}</span> @enderror
            </label>

            <label class="form-field">Thời gian làm
                <input type="text" name="hours" value="{{ old('hours', $job->hours) }}" maxlength="50" placeholder="VD: 3–5 giờ/ngày">
                @error('hours') <span class="form-error">{{ $message }}</span> @enderror
            </label>

            <label class="form-field form-check">
                <input type="hidden" name="is_remote" value="0">
                <input type="checkbox" name="is_remote" value="1" @checked(old('is_remote', $job->is_remote))> Làm từ xa (remote)
            </label>

            <label class="form-field form-field--wide">Kỹ năng bắt buộc
                <input type="text" name="required_skills" value="{{ old('required_skills', $skillsOf(true)) }}" maxlength="500" placeholder="Cách nhau bằng dấu phẩy, VD: PHP, Laravel, SQL">
                <small class="muted">Tính điểm gấp đôi kỹ năng điểm cộng khi so khớp.</small>
                @error('required_skills') <span class="form-error">{{ $message }}</span> @enderror
            </label>

            <label class="form-field form-field--wide">Kỹ năng điểm cộng
                <input type="text" name="optional_skills" value="{{ old('optional_skills', $skillsOf(false)) }}" maxlength="500" placeholder="VD: Docker, Git">
                @error('optional_skills') <span class="form-error">{{ $message }}</span> @enderror
            </label>

            <label class="form-field form-field--wide">Mô tả công việc *
                <textarea name="description" rows="5" required maxlength="5000">{{ old('description', $job->description) }}</textarea>
                @error('description') <span class="form-error">{{ $message }}</span> @enderror
            </label>

            <label class="form-field">Yêu cầu (mỗi dòng một ý)
                <textarea name="requirements" rows="5" maxlength="3000">{{ old('requirements', implode("\n", $job->requirements ?? [])) }}</textarea>
                @error('requirements') <span class="form-error">{{ $message }}</span> @enderror
            </label>

            <label class="form-field">Quyền lợi (mỗi dòng một ý)
                <textarea name="benefits" rows="5" maxlength="3000">{{ old('benefits', implode("\n", $job->benefits ?? [])) }}</textarea>
                @error('benefits') <span class="form-error">{{ $message }}</span> @enderror
            </label>

            <label class="form-field form-field--wide">Ảnh bìa (link https, không bắt buộc)
                <input type="url" name="image" value="{{ old('image', $job->image) }}" maxlength="500" placeholder="https://...">
                @error('image') <span class="form-error">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="panel-actions" style="margin-top:18px">
            <button class="btn btn-primary btn-lg" type="submit">{{ $editing ? 'Lưu thay đổi' : 'Đăng tin' }}</button>
            <a class="btn btn-ghost btn-lg" href="{{ route('employer.jobs.index') }}">Huỷ</a>
        </div>
    </form>
@endsection
