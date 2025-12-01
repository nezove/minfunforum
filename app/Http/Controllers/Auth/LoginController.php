<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Services\SessionLogger;
use App\Models\LoginAttempt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function username()
    {
        return 'username';
    }

    protected function authenticated(Request $request, $user)
    {
        // КРИТИЧЕСКИ ВАЖНО: Регенерируем ID сессии для защиты от Session Fixation
        $request->session()->regenerate();
        
        // ВАЖНО: Логируем вход СРАЗУ после аутентификации
        SessionLogger::logSession($user->id, 'login', $request);

        // Проверяем подозрительную активность
        $suspicious = SessionLogger::checkSuspiciousActivity($user->id);
        
        if ($suspicious['multiple_ips'] || $suspicious['rapid_logins']) {
            session()->flash('warning', 'Обнаружена необычная активность в вашем аккаунте.');
        }
        
        // Проверяем, не заблокирован ли пользователь
        if ($user->isBanned()) {
            $banType = $user->getBanType();
            
            if ($banType === 'permanent') {
                // ПОСТОЯННЫЙ БАН - запрещаем вход
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                $message = 'Ваш аккаунт заблокирован навсегда';
                if ($user->ban_reason) {
                    $message .= '. Причина: ' . $user->ban_reason;
                }

                return redirect()->route('login')->withErrors(['username' => $message]);
            }
            // Для временного бана - разрешаем войти, middleware обработает ограничения
        }

        // Обновляем время последней активности
        $user->update(['last_activity_at' => now()]);

        $intendedUrl = session()->pull('intended_url');
        
        if ($intendedUrl) {
            return redirect($intendedUrl);
        }

        return redirect()->intended($this->redirectPath());
    }

    protected function loggedOut(Request $request)
    {
        // Полная очистка сессии при выходе
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }

    protected function redirectTo()
    {
        return session()->pull('intended_url', '/home');
    }
    /**
 * Показать форму входа
 */
public function showLoginForm()
{
    $ip = request()->ip();
    
    // Безопасная проверка - если модель не создана, пропускаем
    if (class_exists('App\Models\LoginAttempt')) {
        // Проверяем, заблокирован ли IP
        if (LoginAttempt::isIpBlocked($ip)) {
            // ИСПРАВЛЕНИЕ: Получаем время последней неудачной попытки напрямую
            $lastFailedAttempt = LoginAttempt::where('ip_address', $ip)
                ->where('successful', false)
                ->latest('attempted_at')
                ->first();
            
            if ($lastFailedAttempt) {
                // ИСПРАВЛЕНИЕ: Используем copy() чтобы не изменять исходный объект Carbon
                $unblockTime = $lastFailedAttempt->attempted_at->copy()->addHours(48);
                
                // Проверяем, что блокировка ещё действует
                if ($unblockTime->isFuture()) {
                    return view('auth.blocked', [
                        'message' => "Ваш IP заблокирован за превышение лимита попыток входа.",
                        'unblock_time' => $unblockTime
                    ]);
                }
            }
        }
        
        // Проверяем, нужна ли капча
        $requiresCaptcha = LoginAttempt::requiresCaptcha($ip);
    } else {
        $requiresCaptcha = false;
    }
    
    return view('auth.login', compact('requiresCaptcha'));
}

/**
 * Обработка входа в систему
 */
public function login(Request $request)
{
    $ip = $request->ip();
    
    // Проверяем, заблокирован ли IP
    if (LoginAttempt::isIpBlocked($ip)) {
        LoginAttempt::recordAttempt($ip, $request->username, false); // ИЗМЕНЕНО: email на username
        
        return back()->withErrors([
            'username' => 'Ваш IP заблокирован за превышение лимита попыток входа.'
        ])->withInput($request->except('password'));
    }

    // Базовая валидация
    $rules = [
        'username' => 'required|string', // ИЗМЕНЕНО: email на username
        'password' => 'required|string',
    ];
    
    // Проверяем, нужна ли капча
    if (LoginAttempt::requiresCaptcha($ip) || Session::get('requires_captcha')) {
        $rules['h-captcha-response'] = 'required';
        
        // Валидируем капчу
        if (!$this->validateCaptcha($request->input('h-captcha-response'))) {
            LoginAttempt::recordAttempt($ip, $request->username, false); // ИЗМЕНЕНО
            
            return back()->withErrors([
                'h-captcha-response' => 'Пожалуйста, пройдите проверку капчи.'
            ])->withInput($request->except('password'));
        }
    }
    
    $request->validate($rules);

    // Получаем данные для входа
    $credentials = $request->only('username', 'password'); // ИЗМЕНЕНО
    $remember = $request->boolean('remember');

    // Попытка аутентификации
    if (Auth::attempt($credentials, $remember)) {
        // Успешный вход
        LoginAttempt::recordAttempt($ip, $request->username, true); // ИЗМЕНЕНО
        
        // Очищаем флаги капчи
        Session::forget(['requires_captcha', 'captcha_required']);
        
        $request->session()->regenerate();

        // Перенаправляем туда, куда хотел попасть пользователь
        $intendedUrl = Session::pull('intended_url') ?: route('forum.index');
        
        return redirect()->intended($intendedUrl)
            ->with('success', 'Добро пожаловать!');
    }

    // Неудачная попытка входа
    LoginAttempt::recordAttempt($ip, $request->username, false); // ИЗМЕНЕНО
    
    $failedAttempts = LoginAttempt::getFailedAttemptsForIp($ip);
    $attemptsLeft = 8 - $failedAttempts;
    
    $errorMessage = 'Неверный логин или пароль.'; // ИЗМЕНЕНО текст
    
    if ($failedAttempts >= 3 && $attemptsLeft > 0) {
        $errorMessage .= " Осталось {$attemptsLeft} попыток, после чего IP будет заблокирован на 48 часов.";
    }

    return back()->withErrors([
        'username' => $errorMessage, // ИЗМЕНЕНО: email на username
    ])->withInput($request->except('password'));
}


/**
 * Валидация hCaptcha
 */
private function validateCaptcha($captchaResponse)
{
    if (!$captchaResponse) {
        return false;
    }

    $secretKey = env('HCAPTCHA_SECRET_KEY');
    
    if (!$secretKey) {
        \Log::error('hCaptcha secret key not found in environment');
        return false;
    }

    try {
        $response = Http::asForm()->post('https://hcaptcha.com/siteverify', [
            'secret' => $secretKey,
            'response' => $captchaResponse,
            'remoteip' => request()->ip()
        ]);

        $result = $response->json();
        
        return $result['success'] ?? false;
    } catch (\Exception $e) {
        \Log::error('hCaptcha validation error: ' . $e->getMessage());
        return false;
    }
}}