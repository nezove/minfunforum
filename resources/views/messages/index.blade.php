@extends('layouts.app')

@section('title', 'Личные сообщения')

@section('content')
<div class="container-fluid p-0" style="height: calc(100vh - 56px);">
    <div class="row g-0 h-100">
        <!-- Список диалогов -->
        <div class="col-12 col-md-4 col-lg-3 border-end h-100 d-flex flex-column bg-white">
            <!-- Заголовок -->
            <div class="border-bottom p-3 bg-light">
                <h5 class="mb-0">
                    <i class="bi bi-chat-dots-fill me-2"></i>Сообщения
                </h5>
            </div>

            <!-- Список диалогов -->
            <div class="flex-grow-1 overflow-auto">
                @if($conversations->isEmpty())
                    <div class="text-center py-5 px-3">
                        <i class="bi bi-inbox display-1 text-muted d-block mb-3"></i>
                        <h6 class="text-muted">Нет диалогов</h6>
                        <p class="small text-muted mb-0">Начните новый диалог, отправив сообщение пользователю</p>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($conversations as $conversation)
                            @php
                                $otherUser = $conversation->otherUser;
                                $lastMsg = $conversation->lastMsg;
                                $unread = $conversation->unreadCount;
                            @endphp
                            
                            <a href="{{ route('messages.show', $otherUser->id) }}" 
                               class="list-group-item list-group-item-action border-0 border-bottom py-3 {{ $unread > 0 ? 'bg-light' : '' }}">
                                <div class="d-flex">
                                    <!-- Аватар -->
                                    <div class="flex-shrink-0 me-3">
                                        @if($otherUser->avatar)
                                            <img src="{{ asset('storage/' . $otherUser->avatar) }}" 
                                                 alt="{{ $otherUser->name }}" 
                                                 class="rounded-circle"
                                                 style="width: 48px; height: 48px; object-fit: cover;">
                                        @else
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                                 style="width: 48px; height: 48px; font-size: 1.25rem;">
                                                {{ strtoupper(substr($otherUser->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Информация -->
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="mb-0 {{ $unread > 0 ? 'fw-bold' : '' }}">
                                                {{ Str::limit($otherUser->name, 20) }}
                                            </h6>
                                            @if($lastMsg)
                                                <small class="text-muted text-nowrap ms-2">
                                                    {{ $lastMsg->created_at->format('H:i') }}
                                                </small>
                                            @endif
                                        </div>

                                        @if($lastMsg)
                                            <div class="d-flex justify-content-between align-items-center">
                                                <p class="mb-0 text-truncate small {{ $unread > 0 ? 'fw-semibold text-dark' : 'text-muted' }}" style="max-width: 80%;">
                                                    @if($lastMsg->sender_id == auth()->id())
                                                        <i class="bi bi-check-all {{ $lastMsg->is_read ? 'text-primary' : 'text-secondary' }}"></i>
                                                    @endif
                                                    
                                                    @if($lastMsg->hasImage())
                                                        <i class="bi bi-image"></i>
                                                    @endif
                                                    
                                                    {{ $lastMsg->content ? Str::limit($lastMsg->content, 30) : 'Фото' }}
                                                </p>

                                                @if($unread > 0)
                                                    <span class="badge bg-primary rounded-pill">{{ $unread > 99 ? '99+' : $unread }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Заглушка "Выберите диалог" -->
        <div class="col-md-8 col-lg-9 d-none d-md-flex align-items-center justify-content-center bg-light">
            <div class="text-center">
                <i class="bi bi-chat-text display-1 text-muted mb-4"></i>
                <h4 class="text-muted mb-2">Выберите диалог</h4>
                <p class="text-muted">Выберите чат из списка слева<br>или начните новый диалог</p>
            </div>
        </div>
    </div>
</div>
@endsection