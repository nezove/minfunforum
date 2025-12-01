@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h1 class="mb-0">Правила форума</h1>
                </div>

                <div class="card-body">
                    <div class="mb-4">
                        <h2>1. Общие положения</h2>
                        <p>Данные правила являются обязательными для всех пользователей форума. Регистрируясь на форуме, вы автоматически соглашаетесь с данными правилами.</p>
                    </div>

                    <div class="mb-4">
                        <h2>2. Поведение на форуме</h2>
                        <ul>
                            <li>Уважайте других пользователей</li>
                            <li>Не используйте нецензурную лексику</li>
                            <li>Не размещайте спам и рекламу</li>
                            <li>Не нарушайте авторские права</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2>3. Публикация контента</h2>
                        <ul>
                            <li>Запрещено размещение оскорбительного контента</li>
                            <li>Не публикуйте личную информацию других пользователей</li>
                            <li>Не размещайте ссылки на вредоносные сайты</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h2>4. Нарушения и санкции</h2>
                        <p>За нарушение правил могут применяться следующие санкции:</p>
                        <ul>
                            <li>Предупреждение</li>
                            <li>Временная блокировка</li>
                            <li>Постоянная блокировка</li>
                        </ul>
                    </div>

                    <div class="alert alert-info">
                        <strong>Примечание:</strong> Администрация оставляет за собой право изменять данные правила без предварительного уведомления.
                    </div>

                    <div class="text-center">
                        <a href="{{ route('register') }}" class="btn btn-primary">Вернуться к регистрации</a>
                        <a href="{{ route('forum.index') }}" class="btn btn-secondary">На главную</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection