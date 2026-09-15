@extends('layout.app')
@section('title', 'Applications - Jobly')
@section('page', 'applications')

@section('content')
<header class="page-head">
    <div>
      <h1 class="page-title">Tiến trình ứng tuyển</h1>
      <p class="page-sub">Theo dõi hành trình của bạn.</p>
    </div>
    <a class="btn btn-soft btn-sm" href="{{ route('explore') }}" style="margin-top:6px"><i data-lucide="plus"></i> Tìm việc mới</a>
  </header>

  <div class="app-list" id="app-list"></div>
@endsection