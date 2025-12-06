@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-ban me-2"></i>
                        Доступ ограничен
                    </h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-user-slash text-danger" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h5 class="text-danger mb-3">{{ $message }}</h5>
                    
                    <p class="text-muted mb-4">
                        К сожалению, мы не можем предоставить вам доступ к регистрации на нашем форуме.
                        Если вы считаете, что это ошибка, обратитесь к администрации.
                    </p>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="{{ route('forum.index') }}" class="btn btn-primary">
                            <i class="fas fa-home me-1"></i>
                            На главную
                        </a>
                        
                        @auth
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-sign-in-alt me-1"></i>
                                Войти
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection