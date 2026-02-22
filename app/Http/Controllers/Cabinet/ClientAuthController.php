<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\TelegramAuthService;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClientAuthController extends Controller
{
    public function showLogin(): View
    {
        $botUsername = $this->getBotUsername();
        $clients = Client::active()->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        return view('cabinet.login', compact('botUsername', 'clients'));
    }

    public function handlePasswordLogin(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'client_id' => 'required|exists:clients,id',
                'password' => 'required|string',
            ]);

            $client = Client::findOrFail($request->client_id);
            if ($client->status !== 'active') {
                return redirect()->route('cabinet.login')->withErrors(['password' => 'Доступ в личный кабинет отключён для этого клиента.']);
            }

            if (!$client->verifyCabinetPassword($request->password)) {
                return redirect()->route('cabinet.login')->withErrors(['password' => 'Неверный пароль.']);
            }

            $request->session()->put('client_id', $client->id);
            $request->session()->regenerate();
            return redirect()->route('cabinet.dashboard');
        } catch (Throwable $e) {
            try {
                Log::error('Ошибка входа в ЛК', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            } catch (Throwable $logException) {
                // если лог недоступен (нет прав на storage) — не падаем
            }
            $message = config('app.debug')
                ? 'Ошибка входа: ' . $e->getMessage() . ' (файл ' . basename($e->getFile()) . ', строка ' . $e->getLine() . ')'
                : 'Временная ошибка входа. Обратитесь к администратору. Детали в storage/logs/laravel.log';
            return redirect()->route('cabinet.login')->withErrors(['password' => $message]);
        }
    }

    public function handleTelegramCallback(Request $request): RedirectResponse
    {
        $id = $request->get('id');
        $hash = $request->get('hash');
        if (!$id || !$hash) {
            return redirect()->route('cabinet.login')->withErrors(['telegram' => 'Не удалось получить данные от Telegram.']);
        }
        $data = $request->only(['id', 'first_name', 'last_name', 'username', 'photo_url', 'auth_date', 'hash']);
        if (!TelegramAuthService::verifyAuthData($data)) {
            return redirect()->route('cabinet.login')->withErrors(['telegram' => 'Ошибка проверки данных Telegram.']);
        }
        $client = Client::where('telegram_id', $id)->first();
        if (!$client) {
            return redirect()->route('cabinet.login')->withErrors([
                'telegram' => 'Ваш аккаунт Telegram не привязан к карточке клиента. Обратитесь к администратору.',
            ]);
        }
        if ($client->status !== 'active') {
            return redirect()->route('cabinet.login')->withErrors(['telegram' => 'Доступ в личный кабинет отключён.']);
        }
        $request->session()->put('client_id', $client->id);
        $request->session()->regenerate();
        return redirect()->route('cabinet.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('client_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    private function getBotUsername(): string
    {
        return Setting::get('telegram_bot_username', 'NskCapital_bot');
    }
}
