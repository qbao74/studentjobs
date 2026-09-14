@extends('layout.app')

@section('title', 'Jobly — Swipe • Match • Build Your Future')
@section('page', 'home')

@section('content')
  <header class="page-head">
    <div>
      <h1 class="page-title">Chào Bảo! <span class="wave">👋</span></h1>
      <p class="page-sub">Đây là những công việc phù hợp với bạn dựa trên CV.</p>
    </div>
    <div class="head-tools">
      <form class="search-bar" action="{{ url('/explore') }}" method="get">
        <i data-lucide="search"></i>
        <input type="search" name="q" placeholder="Tìm kiếm công việc, công ty..." />
      </form>
      <button class="icon-btn" id="btn-notify" type="button" aria-label="Thông báo">
        <i data-lucide="bell"></i>
        <span class="notify-dot"></span>
      </button>
      <div class="notify-pop" id="notify-pop" hidden>
        <p><strong>Mây Creative</strong> đã xem CV của bạn</p>
        <p><strong>TechWind</strong> mời bạn phỏng vấn Thứ 3</p>
        <p>AI tìm thấy 3 việc mới phù hợp 92%+</p>
        <a href="{{ url('/chat') }}">Mở tin nhắn →</a>
      </div>
    </div>
  </header>

  <section class="discover">
    <div class="card-stack" id="card-stack"></div>
    <p class="swipe-hint"><i data-lucide="move-vertical"></i> Vuốt lên để nhận • Vuốt xuống để bỏ qua</p>
    <div class="swipe-actions">
      <button class="action-btn action-btn--skip" id="btn-skip" type="button">
        <span class="action-orb"><i data-lucide="x"></i></span>Bỏ qua
      </button>
      <button class="action-btn action-btn--info" id="btn-detail" type="button">
        <span class="action-orb"><i data-lucide="info"></i></span>Chi tiết
      </button>
      <button class="action-btn action-btn--apply" id="btn-apply" type="button">
        <span class="action-orb"><i data-lucide="arrow-up"></i></span>Nhận
      </button>
    </div>
  </section>
@endsection