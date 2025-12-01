@foreach($posts as $post)
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="card-title mb-2">
                <a href="{{ $post->permalink }}" class="text-decoration-none">
                    Ответ в теме: {{ $post->topic->title }}
                </a>
            </h6>
            
            <p class="card-text text-muted mb-3">
                {!! Str::limit(strip_tags($post->content), 300) !!}
            </p>
            
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3 small text-muted">
                    <span>
                        <i class="bi bi-person me-1"></i>
                        <a href="{{ route('profile.show', $post->user) }}" class="text-decoration-none">
                            {{ $post->user->name }}
                        </a>
                    </span>
                    <span>
                        <i class="bi bi-folder me-1"></i>
                        {{ $post->topic->category->name }}
                    </span>
                    <span>
                        <i class="bi bi-clock me-1"></i>
                        {{ $post->created_at->diffForHumans() }}
                    </span>
                </div>
                
                <a href="{{ $post->permalink }}" class="btn btn-outline-primary btn-sm">
                    Перейти к ответу
                </a>
            </div>
        </div>
    </div>
@endforeach