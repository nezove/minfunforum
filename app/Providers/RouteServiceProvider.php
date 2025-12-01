<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });

            // Стандартные лимиты
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

   // Лимит для лайков - 30 в минуту на пользователя
    RateLimiter::for('like', function (Request $request) {
        return [
            Limit::perMinute(30)->by($request->user()->id),
            Limit::perMinute(100)->by($request->ip()), // общий лимит по IP
        ];
    });

    // Лимит для создания постов - более строгий
    RateLimiter::for('posts', function (Request $request) {
        return [
            Limit::perMinute(5)->by($request->user()->id)->response(function () {
                return redirect()->back()->withErrors(['content' => 'Слишком много сообщений. Подождите минуту.']);
            }),
            Limit::perHour(50)->by($request->user()->id)->response(function () {
                return redirect()->back()->withErrors(['content' => 'Превышен часовой лимит сообщений.']);
            }),
            Limit::perDay(200)->by($request->user()->id)->response(function () {
                return redirect()->back()->withErrors(['content' => 'Превышен дневной лимит сообщений.']);
            }),
        ];
    });

    // Лимит для создания тем - 5 в час
    RateLimiter::for('topics', function (Request $request) {
        return [
            Limit::perHour(5)->by($request->user()->id),
            Limit::perDay(20)->by($request->user()->id),
        ];
    });

    $this->routes(function () {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    });
    }
    protected function configureRateLimiting()
{
    // Базовые лимиты
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    // Лимит для постов - защита от спама
    RateLimiter::for('posts', function (Request $request) {
        return [
            Limit::perMinute(3)->by($request->user()->id)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Слишком много сообщений. Подождите минуту.'
                ], 429);
            }),
            Limit::perHour(50)->by($request->user()->id),
            Limit::perDay(200)->by($request->user()->id),
        ];
    });

    // Лимит для тем
    RateLimiter::for('topics', function (Request $request) {
        return [
            Limit::perHour(5)->by($request->user()->id),
            Limit::perDay(20)->by($request->user()->id),
        ];
    });

    // Лимит для лайков
    RateLimiter::for('like', function (Request $request) {
        return [
            Limit::perMinute(10)->by($request->user()->id),
            Limit::perHour(100)->by($request->user()->id),
        ];
    });

    // Лимит для административных действий
    RateLimiter::for('admin', function (Request $request) {
        return [
            Limit::perMinute(20)->by($request->user()->id),
            Limit::perHour(200)->by($request->user()->id),
        ];
    });

    // Лимит для входа - защита от брутфорса
    RateLimiter::for('login', function (Request $request) {
        return [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perHour(20)->by($request->ip()),
        ];
    });

    // Лимит для регистрации
    RateLimiter::for('register', function (Request $request) {
        return [
            Limit::perMinute(2)->by($request->ip()),
            Limit::perDay(10)->by($request->ip()),
        ];
    });
}
}
