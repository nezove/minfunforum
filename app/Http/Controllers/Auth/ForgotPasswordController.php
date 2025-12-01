<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:5,1')->only('sendResetLinkEmail'); // 5 попыток в минуту
    }

    /**
     * Отображает форму запроса сброса пароля
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Отправляет ссылку для сброса пароля с проверкой hCaptcha
     */
    public function sendResetLinkEmail(Request $request)
    {
        // Базовые правила валидации
        $rules = [
            'email' => 'required|email|exists:users,email',
        ];

        $messages = [
            'email.required' => 'Поле email обязательно для заполнения.',
            'email.email' => 'Введите корректный email адрес.',
            'email.exists' => 'Пользователь с таким email не найден.',
        ];

        // Добавляем проверку hCaptcha только если она настроена
        if (config('services.hcaptcha.site_key') && config('services.hcaptcha.secret_key')) {
            $rules['h-captcha-response'] = 'required';
            $messages['h-captcha-response.required'] = 'Пройдите проверку капчи.';
        }

        // Создаем валидатор
        $validator = Validator::make($request->all(), $rules, $messages);

        // Дополнительная проверка hCaptcha если она настроена
        if (config('services.hcaptcha.site_key') && config('services.hcaptcha.secret_key')) {
            $validator->after(function ($validator) use ($request) {
                $captchaResponse = $request->input('h-captcha-response');
                
                if (!$captchaResponse) {
                    $validator->errors()->add('h-captcha-response', 'Пройдите проверку капчи.');
                    return;
                }

                if (!$this->validateHCaptcha($captchaResponse)) {
                    $validator->errors()->add('h-captcha-response', 'Проверка капчи не пройдена. Попробуйте еще раз.');
                }
            });
        }

        // Если есть ошибки валидации - возвращаем их
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        // Пытаемся отправить ссылку для сброса
        try {
            $response = $this->broker()->sendResetLink(
                $this->credentials($request)
            );

            if ($response == Password::RESET_LINK_SENT) {
                return back()->with('status', 'Ссылка для сброса пароля отправлена на ваш email.');
            } else {
                Log::warning('Password reset failed', [
                    'email' => $request->email,
                    'response' => $response
                ]);
                return back()->withErrors(['email' => 'Произошла ошибка при отправке ссылки для сброса пароля.']);
            }
        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage(), [
                'email' => $request->email,
                'exception' => $e
            ]);
            return back()->withErrors(['email' => 'Произошла ошибка. Попробуйте позже.']);
        }
    }

    /**
     * Получает учетные данные для сброса пароля
     */
    protected function credentials(Request $request)
    {
        return $request->only('email');
    }

    /**
     * Проверяет hCaptcha
     */
    private function validateHCaptcha($captchaResponse)
    {
        $secretKey = config('services.hcaptcha.secret_key');
        
        if (!$secretKey) {
            // Если секретный ключ не настроен, считаем проверку пройденной
            return true;
        }

        try {
            $response = Http::timeout(10)->asForm()->post('https://hcaptcha.com/siteverify', [
                'secret' => $secretKey,
                'response' => $captchaResponse,
                'remoteip' => request()->ip(),
            ]);

            if (!$response->successful()) {
                Log::error('hCaptcha API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

            $result = $response->json();
            
            Log::info('hCaptcha validation result', [
                'success' => $result['success'] ?? false,
                'error_codes' => $result['error-codes'] ?? []
            ]);

            return isset($result['success']) && $result['success'] === true;
            
        } catch (\Exception $e) {
            Log::error('hCaptcha validation exception: ' . $e->getMessage());
            // В случае ошибки API возвращаем false для безопасности
            return false;
        }
    }
}