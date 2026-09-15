@extends('layout.app')
@section('title', 'Companies - Jobly')
@section('page', 'company')
@section('content')
    <a class="back-link" href="{{ route('explore') }}"><i data-lucide="arrow-left"></i> Khám phá</a>
    <div id="company-root"></div>
@endsection
