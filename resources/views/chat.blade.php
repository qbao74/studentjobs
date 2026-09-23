@extends('layout.app')
@section('title', 'Chat - Jobly')
@section('page', 'chat')
@section('hide-rail', '1')
@section('content')
    <div class="chat-layout" id="chat-layout">
        <aside class="card conv-list">
            <div class="conv-head">
                <h2>Tin nhắn</h2>
                <div class="search-bar search-bar--wide" style="height:44px">
                    <i data-lucide="search"></i>
                    <input type="search" id="conv-search" placeholder="Tìm cuộc trò chuyện..." />
                </div>
            </div>
            <div class="conv-items" id="conv-items"></div>
        </aside>
        <section class="card chat-shell">
            <header class="chat-header">
                <button class="icon-btn icon-btn--ghost chat-back" id="chat-back" type="button" aria-label="Danh sách">
                    <i data-lucide="arrow-left"></i>
                </button>
                <a id="chat-company-link" href="{{ url('/explore') }}">
                    <div class="company-logo" id="chat-logo"></div>
                </a>
                <div>
                    <strong id="chat-name"></strong>
                    <small id="chat-status"></small>
                </div>
                <div class="chat-header-actions">
                    <button class="icon-btn icon-btn--ghost" type="button" id="chat-info-toggle" aria-label="Thông tin"><i
                            data-lucide="info"></i></button>
                </div>
            </header>
            <div class="chat-thread" id="chat-thread"></div>
            <form class="chat-input" id="chat-form">
                <input id="chat-input" type="text" placeholder="Nhập tin nhắn..." autocomplete="off" maxlength="2000" />
                <button class="send-btn" type="submit" aria-label="Gửi"><i data-lucide="send"></i></button>
            </form>
        </section>
        <aside class="card chat-info" id="chat-info"></aside>
    </div>
    <div class="sheet-backdrop" id="chat-info-backdrop"></div>
@endsection
