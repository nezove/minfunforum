@extends('layouts.app')

@section('title', 'Поиск по форуму')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Боковая панель с фильтрами -->
        <div class="col-lg-3 col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-funnel me-2"></i>
                        Фильтры поиска
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('search') }}" id="searchForm">
                        <!-- Поисковый запрос -->
                        <div class="mb-3">
                            <label class="form-label">Поисковый запрос</label>
                            <input type="text" name="q" class="form-control" 
                                   value="{{ $query }}" 
                                   placeholder="Введите ключевые слова..."
                                   minlength="2">
                        </div>
                        
                        <!-- Тип поиска -->
                        <div class="mb-3">
                            <label class="form-label">Что искать</label>
                            <select name="type" class="form-select">
                                <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Все</option>
                                <option value="topics" {{ $type === 'topics' ? 'selected' : '' }}>Темы</option>
                                <option value="posts" {{ $type === 'posts' ? 'selected' : '' }}>Ответы</option>
                                <option value="users" {{ $type === 'users' ? 'selected' : '' }}>Пользователи</option>
                            </select>
                        </div>
                        
                        <!-- Категория -->
                        <div class="mb-3" id="categoryFilter" style="{{ $type === 'users' ? 'display: none;' : '' }}">
                            <label class="form-label">Категория</label>
                            <select name="category" class="form-select">
                                <option value="">Все категории</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $category == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Сортировка -->
                        <div class="mb-3">
                            <label class="form-label">Сортировать по</label>
                            <select name="sort" class="form-select" id="sortSelect">
                                <option value="relevance" {{ $sort === 'relevance' ? 'selected' : '' }}>Релевантности</option>
                                <option value="date" {{ $sort === 'date' ? 'selected' : '' }}>Дате</option>
                                <option value="likes" {{ $sort === 'likes' ? 'selected' : '' }} class="topic-only">Лайкам</option>
                                <option value="views" {{ $sort === 'views' ? 'selected' : '' }} class="topic-only">Просмотрам</option>
                                <option value="replies" {{ $sort === 'replies' ? 'selected' : '' }} class="topic-only">Ответам</option>
                                <option value="posts" {{ $sort === 'posts' ? 'selected' : '' }} class="user-only" style="display: none;">Постам пользователя</option>
                                <option value="topics" {{ $sort === 'topics' ? 'selected' : '' }} class="user-only" style="display: none;">Темам пользователя</option>
                            </select>
                        </div>
                        
                        <!-- Период -->
                        <div class="mb-3">
                            <label class="form-label">Период</label>
                            <select name="date" class="form-select">
                                <option value="">За все время</option>
                                <option value="week" {{ $dateFilter === 'week' ? 'selected' : '' }}>За неделю</option>
                                <option value="month" {{ $dateFilter === 'month' ? 'selected' : '' }}>За месяц</option>
                                <option value="year" {{ $dateFilter === 'year' ? 'selected' : '' }}>За год</option>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i>
                                Найти
                            </button>
                        </div>
                        
                        @if($query)
                            <div class="d-grid mt-2">
                                <a href="{{ route('search') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-circle me-1"></i>
                                    Очистить
                                </a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
            
            <!-- Быстрые фильтры -->
            @if($query)
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Быстрые фильтры</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('search', ['q' => $query, 'type' => 'topics']) }}" 
                               class="btn btn-outline-primary btn-sm {{ $type === 'topics' ? 'active' : '' }}">
                                Только темы
                            </a>
                            <a href="{{ route('search', ['q' => $query, 'type' => 'posts']) }}" 
                               class="btn btn-outline-success btn-sm {{ $type === 'posts' ? 'active' : '' }}">
                                Только ответы
                            </a>
                            <a href="{{ route('search', ['q' => $query, 'type' => 'users']) }}" 
                               class="btn btn-outline-info btn-sm {{ $type === 'users' ? 'active' : '' }}">
                                Только пользователи
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Основной контент -->
        <div class="col-lg-9 col-md-8">
            <!-- Заголовок и статистика -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h4 mb-1">
                        <i class="bi bi-search me-2"></i>
                        Поиск по форуму
                    </h2>
                    @if($query)
                        <p class="text-muted mb-0">
                            Результаты для: <strong>"{{ $query }}"</strong>
                            @if($totalResults > 0)
                                <span class="badge bg-primary ms-2">{{ $totalResults }}</span>
                            @endif
                        </p>
                    @endif
                </div>
            </div>
            
            <!-- Результаты поиска -->
            @if(!$query)
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Поиск по форуму</h5>
                        <p class="text-muted">
                            Введите ключевые слова для поиска тем, ответов или пользователей.<br>
                            Минимальная длина запроса: 2 символа.
                        </p>
                    </div>
                </div>
            @elseif($totalResults === 0)
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Ничего не найдено</h5>
                        <p class="text-muted">
                            По запросу <strong>"{{ $query }}"</strong> ничего не найдено.<br>
                            Попробуйте изменить поисковый запрос или фильтры.
                        </p>
                    </div>
                </div>
            @else
                <!-- Вывод результатов -->
                @if($type === 'users')
                    @include('search.partials.users', ['users' => $results])
                @elseif($type === 'posts')
                    @include('search.partials.posts', ['posts' => $results])
                @else
                    @include('search.partials.topics', ['topics' => $results])
                @endif
                
                <!-- Пагинация -->
                @if($results->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $results->withQueryString()->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="type"]');
    const sortSelect = document.getElementById('sortSelect');
    const categoryFilter = document.getElementById('categoryFilter');
    
    function updateFilters() {
        const selectedType = typeSelect.value;
        const topicOptions = sortSelect.querySelectorAll('.topic-only');
        const userOptions = sortSelect.querySelectorAll('.user-only');
        
        // Показываем/скрываем категории
        if (selectedType === 'users') {
            categoryFilter.style.display = 'none';
        } else {
            categoryFilter.style.display = 'block';
        }
        
        // Показываем/скрываем опции сортировки
        topicOptions.forEach(option => {
            if (selectedType === 'users') {
                option.style.display = 'none';
            } else {
                option.style.display = 'block';
            }
        });
        
        userOptions.forEach(option => {
            if (selectedType === 'users') {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });
        
        // Сбрасываем сортировку если она не подходит
        if (selectedType === 'users' && ['likes', 'views', 'replies'].includes(sortSelect.value)) {
            sortSelect.value = 'relevance';
        } else if (selectedType !== 'users' && ['posts', 'topics'].includes(sortSelect.value)) {
            sortSelect.value = 'relevance';
        }
    }
    
    typeSelect.addEventListener('change', updateFilters);
    updateFilters(); // Инициализация
});
</script>
@endsection