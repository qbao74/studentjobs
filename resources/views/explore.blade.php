@extends('layout.app')

@section('title', 'Khám phá — Jobly')
@section('page', 'explore')

@section('content')
  <header class="page-head">
    <div>
      <h1 class="page-title" id="page-title">Khám phá thêm</h1>
      <p class="page-sub" id="page-sub">Những cơ hội khác có thể phù hợp với bạn.</p>
    </div>
    <span class="chip" id="result-count" style="margin-top:8px">8 công việc</span>
  </header>

  <form class="search-bar search-bar--wide" id="explore-form">
    <i data-lucide="search"></i>
    <input type="search" id="explore-search" placeholder="Tìm theo vị trí, công ty, kỹ năng..." autocomplete="off" />
  </form>

  <div class="chip-row">
    <button class="filter-chip is-active" data-filter="all" type="button">Tất cả</button>
    <button class="filter-chip" data-filter="Part-time" type="button">Part-time</button>
    <button class="filter-chip" data-filter="Thực tập" type="button">Internship</button>
    <button class="filter-chip" data-filter="Remote" type="button">Remote</button>
    <button class="filter-chip" data-filter="Full-time" type="button">Full-time</button>
    <button class="filter-chip" data-filter="saved" type="button">♡ Đã lưu</button>
  </div>

  <div class="job-list" id="job-list"></div>
@endsection