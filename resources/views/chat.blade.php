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
                <a id="chat-company-link" href="company.html">
                    <div class="company-logo" id="chat-logo">M</div>
                </a>
                <div>
                    <strong id="chat-name">Mây Creative</strong>
                    <small id="chat-status"><span class="online-dot"></span>Đang hoạt động</small>
                </div>
                <div class="chat-header-actions">
                    <button class="icon-btn icon-btn--ghost" type="button" aria-label="Gọi thoại"
                        data-toast="Tính năng gọi sẽ có khi kết nối API"><i data-lucide="phone"></i></button>
                    <button class="icon-btn icon-btn--ghost" type="button" aria-label="Gọi video"
                        data-toast="Tính năng gọi video sẽ có khi kết nối API"><i data-lucide="video"></i></button>
                    <button class="icon-btn icon-btn--ghost" type="button" id="chat-info-toggle" aria-label="Thông tin"><i
                            data-lucide="info"></i></button>
                </div>
            </header>
            <div class="chat-thread" id="chat-thread"></div>
            <div class="quick-replies" id="quick-replies">
                <button type="button">Thứ 2 • 10:00</button>
                <button type="button">Thứ 2 • 14:00</button>
                <button type="button">Thứ 3 • 09:00</button>
            </div>
            <form class="chat-input" id="chat-form">
                <button class="icon-btn" type="button" aria-label="Đính kèm"><i data-lucide="paperclip"></i></button>
                <input id="chat-input" type="text" placeholder="Nhập tin nhắn..." autocomplete="off" />
                <button class="icon-btn" type="button" aria-label="Emoji"><i data-lucide="smile"></i></button>
                <button class="send-btn" type="submit" aria-label="Gửi"><i data-lucide="send"></i></button>
            </form>
        </section>
        <aside class="card chat-info" id="chat-info"></aside>
    </div>
    <div class="sheet-backdrop" id="chat-info-backdrop"></div>
@endsection
