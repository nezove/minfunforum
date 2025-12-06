<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:10,1')->only('reset'); // 10 попыток в минуту
    }

    /**
     * Отображает форму сброса пароля
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset')->with([
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Сбрасывает пароль БЕЗ проверки hCaptcha
     */
    public function reset(Request $request)
    {
        // Простая валидация без hCaptcha
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], [
            'token.required' => 'Неверный токен сброса пароля.',
            'email.required' => 'Поле email обязательно для заполнения.',
            'email.email' => 'Введите корректный email адрес.',
            'password.required' => 'Поле пароль обязательно для заполнения.',
            'password.min' => 'Пароль должен содержать минимум 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        // Сбрасываем пароль без дополнительных проверок
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        return $response == Password::PASSWORD_RESET
                    ? $this->sendResetResponse($request, $response)
                    : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Сбрасывает пароль пользователя
     */
    protected function resetPassword($user, $password)
    {
        $user->password = Hash::make($password);
        $user->setRememberToken(Str::random(60));
        $user->save();

        event(new PasswordReset($user));

        $this->guard()->login($user);
    }

    /**
     * Получает учетные данные для сброса пароля
     */
    protected function credentials(Request $request)
    {
        return $request->only('email', 'password', 'password_confirmation', 'token');
    }

    /**
     * Ответ при успешном сбросе пароля
     */
    protected function sendResetResponse(Request $request, $response)
    {
        return redirect($this->redirectPath())->with('status', 'Пароль успешно изменен! Вы автоматически вошли в систему.');
    }

    /**
     * Ответ при неудачном сбросе пароля
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Ссылка для сброса пароля недействительна или истекла.']);
    }
}