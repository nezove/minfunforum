@extends('layouts.app')

@section('title', 'Управление наградами')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">
                    <i class="bi bi-trophy text-warning me-2"></i>
                    Управление наградами
                </h1>
                <div>
                    <a href="{{ route('moderation.achievements.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Создать награду
                    </a>
                    <form action="{{ route('moderation.achievements.checkAll') }}" method="POST" class="d-inline ms-2">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('Запустить проверку всех пользователей на получение автоматических наград?')">
                            <i class="bi bi-arrow-clockwise me-1"></i>Проверить всех
                        </button>
                    </form>
                    <a href="{{ route('moderation.index') }}" class="btn btn-secondary ms-2">
                        <i class="bi bi-arrow-left me-1"></i>Назад
                    </a>
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Порядок</th>
                                    <th style="width: 100px;">Иконка</th>
                                    <th>Название</th>
                                    <th>Описание</th>
                                    <th style="width: 120px;">Тип</th>
                                    <th style="width: 150px;">Условие</th>
                                    <th style="width: 100px;">Статус</th>
                                    <th style="width: 100px;">Получено</th>
                                    <th style="width: 150px;" class="text-end">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($achievements as $achievement)
                                <tr>
                                    <td class="text-center">{{ $achievement->display_order }}</td>
                                    <td>
                                        @if($achievement->icon)
                                        <img src="{{ asset('storage/' . $achievement->icon) }}" alt="{{ $achievement->name }}" style="max-width: 64px; max-height: 64px;">
                                        @else
                                        <img src="{{ asset('images/achievements/default.png') }}" alt="Default" style="max-width: 64px; max-height: 64px;">
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $achievement->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $achievement->slug }}</small>
                                    </td>
                                    <td>{{ Str::limit($achievement->description, 60) }}</td>
                                    <td>
                                        @if($achievement->type === 'auto')
                                        <span class="badge bg-primary">Автоматическая</span>
                                        @else
                                        <span class="badge bg-warning">Ручная</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($achievement->type === 'auto')
                                        <small>
                                            @if($achievement->condition_type === 'posts_count')
                                            {{ $achievement->condition_value }} постов
                                            @elseif($achievement->condition_type === 'topics_count')
                                            {{ $achievement->condition_value }} тем
                                            @elseif($achievement->condition_type === 'days_active')
                                            {{ $achievement->condition_value }} дней
                                            @endif
                                        </small>
                                        @else
                                        <small class="text-muted">-</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($achievement->is_active)
                                        <span class="badge bg-success">Активна</span>
                                        @else
                                        <span class="badge bg-secondary">Отключена</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $achievement->users()->count() }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('moderation.achievements.edit', $achievement) }}" class="btn btn-sm btn-outline-primary" title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('moderation.achievements.destroy', $achievement) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить награду?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Удалить">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        Наград пока нет. Создайте первую награду.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                {{ $achievements->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
