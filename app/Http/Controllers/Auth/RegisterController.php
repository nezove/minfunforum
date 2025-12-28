<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SessionLogger;
use App\Services\StopForumSpamService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function validator(array $data)
{
    $rules = [
        'username' => ['required', 'string', 'max:255', 'unique:users', 'regex:/^[a-zA-Z0-9_]+$/'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'terms_accepted' => ['required', 'accepted'],
    ];

    $messages = [
        // Username
        'username.required' => 'Поле логин обязательно для заполнения.',
        'username.string' => 'Логин должен быть строкой.',
        'username.max' => 'Логин не может быть длиннее :max символов.',
        'username.unique' => 'Такой логин уже занят.',
        'username.regex' => 'Логин может содержать только латинские буквы, цифры и знак подчеркивания.',

        // Email
        'email.required' => 'Поле email обязательно для заполнения.',
        'email.string' => 'Email должен быть строкой.',
        'email.email' => 'Введите корректный email адрес.',
        'email.max' => 'Email не может быть длиннее :max символов.',
        'email.unique' => 'Пользователь с таким email уже зарегистрирован.',

        // Password
        'password.required' => 'Поле пароль обязательно для заполнения.',
        'password.string' => 'Пароль должен быть строкой.',
        'password.min' => 'Пароль должен содержать минимум :min символов.',
        'password.confirmed' => 'Пароли не совпадают.',

        // Terms
        'terms_accepted.required' => 'Необходимо принять правила форума.',
        'terms_accepted.accepted' => 'Необходимо принять правила форума.',
    ];

    // Добавляем правило hCaptcha только если ключи настроены
    if (config('services.hcaptcha.site_key') && config('services.hcaptcha.secret_key')) {
        $rules['h-captcha-response'] = ['required'];
        $messages['h-captcha-response.required'] = 'Пожалуйста, подтвердите, что вы не робот.';
    }

    $validator = Validator::make($data, $rules, $messages);

    // Проверяем hCaptcha, если настроена
    if (isset($data['h-captcha-response']) && config('services.hcaptcha.secret_key')) {
        $validator->after(function ($validator) use ($data) {
            if (!$this->validateHCaptcha($data['h-captcha-response'])) {
                $validator->errors()->add('h-captcha-response', 'Проверка капчи не пройдена. Попробуйте еще раз.');
            }
        });
    }

    // НОВОЕ: Проверка на спам через StopForumSpam
    $validator->after(function ($validator) use ($data) {
        $this->checkForSpam($validator, $data);
    });

    return $validator;
}


    protected function create(array $data)
{
    $user = User::create([
        'username' => $data['username'],
        'name' => $data['username'], // Добавьте эту строку
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
    ]);

    // Создаем настройки уведомлений для нового пользователя
    \App\Models\NotificationSettings::create([
        'user_id' => $user->id,
        'notify_reply' => true,
        'notify_reply_to_post' => true,
        'notify_mention' => true,
        'notify_mention_topic' => true,
        'notify_like_topic' => true,
        'notify_like_post' => true,
        'notify_topic_deleted' => true,
        'notify_post_deleted' => true,
        'notify_topic_moved' => true,
        'notify_bans' => true,
        'notify_wall_post' => true,
        'notify_wall_comment' => true,
    ]);

    // Логируем регистрацию
    SessionLogger::logSession($user->id, 'registration', request());

    return $user;
}

/**
 * Проверка на спам через StopForumSpam
 */
private function checkForSpam($validator, array $data)
{
    // Проверяем только если сервис включен
    if (!config('services.stopforumspam.enabled')) {
        return;
    }

    try {
        $stopForumSpamService = app(StopForumSpamService::class);
        $userIp = StopForumSpamService::getUserIp();
        
        // Проверяем IP (дублирующая проверка для безопасности)
        if ($stopForumSpamService->checkIp($userIp)) {
            $validator->errors()->add('email', 'Регистрация недоступна с вашего IP адреса.');
            return;
        }

        // Проверяем email на спам
        if (isset($data['email']) && $stopForumSpamService->checkEmail($data['email'])) {
            $validator->errors()->add('email', 'Регистрация с данным email адресом невозможна.');
            return;
        }

    } catch (\Exception $e) {
        // Логируем ошибку, но не блокируем регистрацию
        \Illuminate\Support\Facades\Log::error('StopForumSpam check failed during registration', [
            'error' => $e->getMessage(),
            'user_ip' => $userIp ?? 'unknown',
            'email' => $data['email'] ?? 'unknown'
        ]);
    }
}

    private function validateHCaptcha($captchaResponse)
    {
        $secretKey = config('services.hcaptcha.secret_key');
        
        if (!$secretKey) {
            // Если секретный ключ не настроен, пропускаем проверку
            return true;
        }

        try {
            $response = Http::timeout(10)->asForm()->post('https://hcaptcha.com/siteverify', [
                'secret' => $secretKey,
                'response' => $captchaResponse,
                'remoteip' => request()->ip(),
            ]);

            $result = $response->json();

            return isset($result['success']) && $result['success'] === true;
            
        } catch (\Exception $e) {
            // В случае ошибки API, логируем и разрешаем регистрацию
            \Log::error('hCaptcha validation error: ' . $e->getMessage());
            return true;
        }
    }

    // AJAX проверка доступности логина
    public function checkUsername(Request $request)
    {
        $username = $request->query('username');
        
        if (!$username) {
            return response()->json(['available' => false, 'message' => 'Логин не может быть пустым']);
        }

        // Проверка на латиницу
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return response()->json([
                'available' => false, 
                'message' => 'Логин может содержать только латинские буквы, цифры и знак подчеркивания'
            ]);
        }

        $exists = User::where('username', $username)->exists();
        
        if ($exists) {
            // Предлагаем альтернативы
            $suggestions = [];
            for ($i = 1; $i <= 3; $i++) {
                $suggestion = $username . $i;
                if (!User::where('username', $suggestion)->exists()) {
                    $suggestions[] = $suggestion;
                }
            }
            
            return response()->json([
                'available' => false,
                'message' => 'Логин уже занят',
                'suggestions' => $suggestions
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Логин доступен'
        ]);
    }

    protected function redirectTo()
    {
        return session()->pull('intended_url', '/home');
    }
}