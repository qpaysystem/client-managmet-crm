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
        $defaultProvider = 'openai';
        $provider = Setting::get('ai_provider', $defaultProvider);

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
            'telegram_webhook_secret' => Setting::get('telegram_webhook_secret', ''),
            'telegram_group_assistant_reply' => Setting::get('telegram_group_assistant_reply', '1'),
            'telegram_group_ai_all' => Setting::get('telegram_group_ai_all', '1'),
            'telegram_group_ai_crm' => Setting::get('telegram_group_ai_crm', '1'),
            'ai_include_crm_snapshot' => Setting::get('ai_include_crm_snapshot', '0'),
            // New universal AI settings (preferred)
            'ai_provider' => $provider,
            'ai_model' => Setting::get('ai_model', $provider === 'deepseek'
                ? config('services.deepseek.model', 'deepseek-chat')
                : config('services.openai.model', 'gpt-4.1-mini')),
            'ai_base_url' => Setting::get('ai_base_url', $provider === 'deepseek'
                ? config('services.deepseek.base_url', 'https://api.deepseek.com/v1')
                : config('services.openai.base_url', 'https://api.openai.com/v1')),

            // Backward-compatible OpenAI settings (legacy keys)
            'openai_model' => Setting::get('openai_model', config('services.openai.model', 'gpt-4.1-mini')),
            'openai_base_url' => Setting::get('openai_base_url', config('services.openai.base_url', 'https://api.openai.com/v1')),
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
            'telegram_webhook_secret' => 'nullable|string|max:255',
            'telegram_webhook_secret_clear' => 'nullable|in:0,1',
            'telegram_group_assistant_reply' => 'in:0,1',
            'telegram_group_ai_all' => 'in:0,1',
            'telegram_group_ai_crm' => 'in:0,1',
            'ai_include_crm_snapshot' => 'in:0,1',
            // New AI settings
            'ai_provider' => 'nullable|in:openai,deepseek',
            'ai_api_key' => 'nullable|string|max:500',
            'ai_model' => 'nullable|string|max:100',
            'ai_base_url' => 'nullable|string|max:255',

            // Legacy OpenAI settings
            'openai_api_key' => 'nullable|string|max:500',
            'openai_model' => 'nullable|string|max:100',
            'openai_base_url' => 'nullable|string|max:255',
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
        if ($request->get('telegram_webhook_secret_clear') === '1') {
            Setting::set('telegram_webhook_secret', '');
        } elseif ($request->filled('telegram_webhook_secret')) {
            Setting::set('telegram_webhook_secret', trim((string) $request->get('telegram_webhook_secret')));
        }
        Setting::set('telegram_group_assistant_reply', $request->get('telegram_group_assistant_reply', '1'));
        Setting::set('telegram_group_ai_all', $request->get('telegram_group_ai_all', '1'));
        Setting::set('telegram_group_ai_crm', $request->get('telegram_group_ai_crm', '1'));
        Setting::set('ai_include_crm_snapshot', $request->get('ai_include_crm_snapshot', '0'));

        if ($request->filled('ai_provider')) {
            Setting::set('ai_provider', $request->get('ai_provider'));
        }
        // AI settings: do not overwrite api key if empty
        if ($request->filled('ai_api_key')) {
            Setting::set('ai_api_key', $request->get('ai_api_key'));
        }
        Setting::set('ai_model', $request->get('ai_model', ''));
        Setting::set('ai_base_url', $request->get('ai_base_url', ''));

        // OpenAI settings: do not overwrite api key if empty
        if ($request->filled('openai_api_key')) {
            Setting::set('openai_api_key', $request->get('openai_api_key'));
        }
        Setting::set('openai_model', $request->get('openai_model', ''));
        Setting::set('openai_base_url', $request->get('openai_base_url', ''));

        return back()->with('success', 'Настройки сохранены.');
    }
}
