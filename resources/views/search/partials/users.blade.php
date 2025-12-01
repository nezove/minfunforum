@foreach($users as $user)
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="{{ $user->avatar_url }}" 
                         class="rounded-circle mb-2" 
                         width="60" height="60" 
                         alt="Avatar">
                </div>
                
                <div class="col-md-6">
                    <h6 class="card-title mb-1">
                        <a href="{{ route('profile.show', $user) }}" class="text-decoration-none">
                            {{ $user->name }}
                        </a>
                        <span class="badge bg-{{ $user->role_color }} ms-2">
                            {{ $user->role_name }}
                        </span>
                    </h6>
                    
                    <p class="text-muted mb-2">@{{ $user->username }}</p>
                    
                    @if($user->bio)
                        <p class="card-text small text-muted mb-2">
                            {{ Str::limit($user->bio, 150) }}
                        </p>
                    @endif
                    
                    <div class="small text-muted">
                        <i class="bi bi-calendar me-1"></i>
                        Регистрация: {{ $user->created_at->format('d.m.Y') }}
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="small text-muted">Темы</div>
                            <div class="fw-semibold text-primary">{{ $user->topics_count }}</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Ответы</div>
                            <div class="fw-semibold text-success">{{ $user->posts_count }}</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Рейтинг</div>
                            <div class="fw-semibold text-warning">{{ $user->rating ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach