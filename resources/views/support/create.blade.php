@extends(auth()->user()->hasRole(\App\Enums\Role::Student) ? 'layout.app' : 'layout.panel')
@section('title', 'Tạo yêu cầu hỗ trợ')
@section('page', 'support')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Tạo yêu cầu hỗ trợ</h1>
            <p class="page-sub">Mô tả rõ vấn đề để quản trị viên phản hồi đúng.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('support.index') }}">Quay lại</a>
    </header>

    <form method="POST" action="{{ route('support.store') }}" class="card section panel-form">
        @csrf
        <label class="form-field">Phân loại *
            <select name="category" required>
                @foreach ($categories as $category)
                    <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </select>
            @error('category') <span class="form-error">{{ $message }}</span> @enderror
        </label>
        <label class="form-field form-field--wide">Tiêu đề *
            <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="150">
            @error('subject') <span class="form-error">{{ $message }}</span> @enderror
        </label>
        <label class="form-field form-field--wide">Nội dung *
            <textarea name="body" rows="6" required minlength="10" maxlength="5000">{{ old('body') }}</textarea>
            @error('body') <span class="form-error">{{ $message }}</span> @enderror
        </label>
        <button class="btn btn-primary" type="submit">Gửi yêu cầu</button>
    </form>
@endsection
