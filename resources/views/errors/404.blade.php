@extends('layouts.app')

@section('title', 'Страница не найдена')

@section('content')
<div class="container">
    <div class="row justify-content-center min-vh-75 align-items-center">
        <div class="col-lg-6 col-md-8 text-center">
            <!-- Анимированная иконка 404 -->
            <div class="error-animation mb-4">
                <div class="error-number">
                    <span class="digit-4">4</span>
                    <span class="digit-0">
                        <i class="bi bi-search" style="font-size: 4rem; color: #6c757d;"></i>
                    </span>
                    <span class="digit-4">4</span>
                </div>
            </div>

            <!-- Заголовок -->
            <h1 class="display-4 fw-bold text-primary mb-3">Страница не найдена</h1>
            
            <!-- Описание -->
            <p class="lead text-muted mb-4">
                К сожалению, страница, которую вы ищете, не существует или была удалена.
                Возможно, в адресе есть опечатка?
            </p>

            <!-- Возможные причины -->
            <div class="alert alert-light border-0 shadow-sm mb-4">
                <h6 class="alert-heading mb-3">
                    <i class="bi bi-lightbulb text-warning me-2"></i>
                    Возможные причины:
                </h6>
                <ul class="list-unstyled mb-0 text-start">
                    <li class="mb-2">
                        <i class="bi bi-arrow-right text-muted me-2"></i>
                        Тема или сообщение были удалены
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-arrow-right text-muted me-2"></i>
                        Неправильно введен адрес страницы
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-arrow-right text-muted me-2"></i>
                        Устаревшая ссылка из поисковика
                    </li>
                    <li>
                        <i class="bi bi-arrow-right text-muted me-2"></i>
                        У вас нет доступа к этой странице
                    </li>
                </ul>
            </div>

            <!-- Кнопки действий -->
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="{{ route('forum.index') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-house me-2"></i>
                    На главную
                </a>
                <button onclick="history.back()" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-left me-2"></i>
                    Назад
                </button>
                <a href="{{ route('topics.create') }}" class="btn btn-outline-success btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>
                    Создать тему
                </a>
            </div>

            <!-- Поиск -->
            <div class="mt-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="bi bi-search me-2"></i>
                            Попробуйте найти то, что искали:
                        </h6>
                        <form action="{{ route('forum.index') }}" method="GET" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" 
                                   placeholder="Поиск по темам..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Популярные темы -->
            <div class="mt-5">
                <h6 class="text-muted mb-3">Популярные темы:</h6>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    @php
                        $popularTopics = \App\Models\Topic::orderBy('views_count', 'desc')
                                                         ->limit(5)
                                                         ->get();
                    @endphp
                    @foreach($popularTopics as $topic)
                        <a href="{{ route('topics.show', $topic->id) }}" 
                           class="btn btn-outline-primary btn-sm">
                            {{ Str::limit($topic->title, 30) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.min-vh-75 {
    min-height: 75vh;
}

.error-animation {
    perspective: 1000px;
}

.error-number {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    font-size: 6rem;
    font-weight: bold;
    color: #0d6efd;
    margin-bottom: 2rem;
}

.digit-4, .digit-0 {
    display: inline-block;
    animation: bounce 2s infinite;
}

.digit-4:first-child {
    animation-delay: 0s;
}

.digit-0 {
    animation-delay: 0.3s;
}

.digit-4:last-child {
    animation-delay: 0.6s;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-20px);
    }
    60% {
        transform: translateY(-10px);
    }
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
}

.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}
</style>
@endsection