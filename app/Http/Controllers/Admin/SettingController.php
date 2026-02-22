<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = [
            'currency' => Setting::get('currency', 'RUB'),
            'max_upload_mb' => Setting::get('max_upload_mb', 5),
            'mail_notifications' => Setting::get('mail_notifications', '0'),
            'telegram_bot_token' => Setting::get('telegram_bot_token', ''),
            'telegram_bot_username' => Setting::get('telegram_bot_username', ''),
            'telegram_chat_id' => Setting::get('telegram_chat_id', ''),
            'telegram_notify_transactions' => Setting::get('telegram_notify_transactions', '0'),
            'telegram_notify_tasks' => Setting::get('telegram_notify_tasks', '0'),
            'telegram_notify_stages' => Setting::get('telegram_notify_stages', '0'),
        ];
        return view('admin.settings.index', compact('settings'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'currency' => 'required|string|max:10',
            'max_upload_mb' => 'required|integer|min:1|max:50',
            'mail_notifications' => 'in:0,1',
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_bot_username' => 'nullable|string|max:100',
            'telegram_chat_id' => 'nullable|string|max:50',
            'telegram_notify_transactions' => 'in:0,1',
            'telegram_notify_tasks' => 'in:0,1',
            'telegram_notify_stages' => 'in:0,1',
        ]);

        Setting::set('currency', $request->currency);
        Setting::set('max_upload_mb', $request->max_upload_mb);
        Setting::set('mail_notifications', $request->get('mail_notifications', '0'));
        Setting::set('telegram_bot_token', $request->get('telegram_bot_token', ''));
        Setting::set('telegram_bot_username', $request->get('telegram_bot_username', ''));
        Setting::set('telegram_chat_id', $request->get('telegram_chat_id', ''));
        Setting::set('telegram_notify_transactions', $request->get('telegram_notify_transactions', '0'));
        Setting::set('telegram_notify_tasks', $request->get('telegram_notify_tasks', '0'));
        Setting::set('telegram_notify_stages', $request->get('telegram_notify_stages', '0'));

        return back()->with('success', 'Настройки сохранены.');
    }
}
