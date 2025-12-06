@foreach($topics as $topic)
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h6 class="card-title mb-2">
                        <a href="{{ route('topics.show', $topic) }}" class="text-decoration-none">
                            {{ $topic->title }}
                        </a>
                        @if($topic->is_closed)
                            <span class="badge bg-secondary ms-2">Закрыто</span>
                        @endif
                        @if($topic->pin_type === 'global')
                            <span class="badge bg-warning ms-1">Глобальный пин</span>
                        @elseif($topic->pin_type === 'category')
                            <span class="badge bg-info ms-1">Закреплено</span>
                        @endif
                    </h6>
                    
                    <p class="card-text text-muted small mb-2">
                        {!! Str::limit(strip_tags($topic->content), 200) !!}
                    </p>
                    
                    <div class="d-flex align-items-center gap-3 small text-muted">
                        <span>
                            <i class="bi bi-person me-1"></i>
                            <a href="{{ route('profile.show', $topic->user) }}" class="text-decoration-none">
                                {{ $topic->user->name }}
                            </a>
                        </span>
                        <span>
                            <i class="bi bi-folder me-1"></i>
                            {{ $topic->category->name }}
                        </span>
                        <span>
                            <i class="bi bi-clock me-1"></i>
                            {{ $topic->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="small text-muted">Ответы</div>
                            <div class="fw-semibold">{{ $topic->replies_count ?? 0 }}</div>
                        </div>
                        <div class="col-3">
                            <div class="small text-muted">Лайки</div>
                            <div class="fw-semibold text-danger">{{ $topic->likes_count ?? 0 }}</div>
                        </div>
                        <div class="col-3">
                            <div class="small text-muted">Просмотры</div>
                            <div class="fw-semibold text-info">{{ $topic->views ?? 0 }}</div>
                        </div>
                        <div class="col-3">
                            @if($topic->lastPost)
                                <div class="small text-muted">Последний</div>
                                <div class="small">
                                    <a href="{{ route('profile.show', $topic->lastPost->user) }}" class="text-decoration-none">
                                        {{ $topic->lastPost->user->name }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach