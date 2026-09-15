@extends('layout.app')
@section('title', 'Profile - Jobly')
@section('page', 'profile')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">Hồ sơ của bạn</h1>
            <p class="page-sub">AI dùng những thông tin này để đề xuất việc phù hợp.</p>
        </div>
    </header>
    <div id="profile-root"></div>
@endsection
