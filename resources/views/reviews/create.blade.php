@extends('layout.app')
@section('title', 'Đánh giá công ty')
@section('page', 'reviews')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Đánh giá công ty</h1>
            <p class="page-sub">Chỉ hiện công ty mà đơn của bạn đã được xác nhận trúng tuyển. Hệ thống kiểm tra lại ở máy chủ, không tin dữ liệu trình duyệt.</p>
        </div>
    </header>

    @if ($companies->isEmpty())
        <div class="card empty-deck">
            <div class="emoji">🔒</div>
            <h2>Chưa có công ty nào để đánh giá</h2>
            <p>Bạn cần trúng tuyển (trạng thái đơn là Trúng tuyển) và chưa gửi đánh giá trước đó.</p>
            <a class="btn btn-primary" href="{{ route('applications') }}">Xem đơn ứng tuyển</a>
        </div>
    @else
        @foreach ($companies as $company)
            <form method="POST" action="{{ route('companies.reviews.store', $company) }}" class="card section panel-form">
                @csrf
                <h2>{{ $company->name }}</h2>
                <label class="form-field">Điểm (1–5) *
                    <select name="rating" required>
                        @for ($n = 5; $n >= 1; $n--)
                            <option value="{{ $n }}" @selected((int) old('rating', 5) === $n)>{{ $n }} sao</option>
                        @endfor
                    </select>
                    @error('rating') <span class="form-error">{{ $message }}</span> @enderror
                </label>
                <label class="form-field form-field--wide">Nhận xét *
                    <textarea name="comment" rows="4" required minlength="10" maxlength="2000" placeholder="Chia sẻ trải nghiệm làm việc thực tế của bạn">{{ old('comment') }}</textarea>
                    @error('comment') <span class="form-error">{{ $message }}</span> @enderror
                </label>
                <button class="btn btn-primary" type="submit">Gửi đánh giá</button>
            </form>
        @endforeach
    @endif
@endsection
