@extends('layout.app')
@section('title', 'Đánh giá '.$company->name)
@section('page', 'reviews')

@section('content')
    <a class="back-link" href="{{ route('companies') }}?id={{ $company->slug }}"><i data-lucide="arrow-left"></i> {{ $company->name }}</a>
    <header class="page-head">
        <div>
            <h1 class="page-title">Đánh giá {{ $company->name }}</h1>
            <p class="page-sub">
                @if ($company->reviews_count)
                    {{ number_format($company->rating, 1) }}/5 · {{ $company->reviews_count }} lượt đánh giá (tính từ dữ liệu đã lưu).
                @else
                    Chưa có đánh giá.
                @endif
            </p>
        </div>
        @if ($canReview)
            <a class="btn btn-primary" href="{{ route('reviews.create', ['company' => $company->slug]) }}">Viết đánh giá</a>
        @endif
    </header>

    @if ($myReview)
        <p class="muted">Bạn đã đánh giá công ty này {{ $myReview->rating }}/5.</p>
    @endif

    @forelse ($reviews as $review)
        <article class="card section">
            <h2>{{ $review->student->user->name }} · {{ $review->rating }}/5</h2>
            <p>{{ $review->comment }}</p>
            <small class="muted">{{ $review->created_at->format('d/m/Y') }}</small>
        </article>
    @empty
        <div class="card empty-deck">
            <div class="emoji">⭐</div>
            <h2>Chưa có đánh giá</h2>
            <p>Chỉ sinh viên đã trúng tuyển mới được gửi đánh giá.</p>
        </div>
    @endforelse

    {{ $reviews->links('pagination.simple') }}
@endsection
