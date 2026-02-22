<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BalanceTransaction;
use App\Models\Client;
use App\Models\CustomField;
use App\Models\Product;
use App\Models\Project;
use App\Services\PushNotificationService;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $query = Client::query()->with('customValues.customField');

        if ($search = $request->get('search')) {
            $query->search($search);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $clients = $query->latest()->paginate(20)->withQueryString();
        return view('admin.clients.index', compact('clients'));
    }

    public function create(): View
    {
        $customFields = CustomField::active()->ordered()->get();
        return view('admin.clients.create', compact('customFields'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'required|string|max:50',
            'telegram_id' => 'nullable|string|regex:/^\d+$/',
            'telegram_username' => 'nullable|string|max:100',
            'cabinet_password' => 'nullable|string|min:4|max:255',
            'registered_at' => 'required|date',
            'status' => 'in:active,inactive',
        ]);

        $validated['balance'] = 0;
        $validated['telegram_id'] = $request->filled('telegram_id') ? (int) $request->telegram_id : null;
        $validated['telegram_username'] = $request->filled('telegram_username') ? preg_replace('/^@/', '', trim($request->telegram_username)) : null;
        if ($request->filled('cabinet_password')) {
            $validated['cabinet_password'] = Hash::make($request->cabinet_password);
        }
        $client = Client::create($validated);

        $this->saveCustomValues($client, $request);
        $this->logActivity('created', $client, null, $client->toArray());

        return redirect()->route('admin.clients.show', $client)->with('success', 'Клиент создан.');
    }

    public function show(Client $client): View
    {
        $client->load('customValues.customField', 'balanceTransactions.product', 'balanceTransactions.project', 'balanceTransactions.projectExpenseItem');
        $products = Product::orderBy('name')->get(['id', 'name']);
        $projects = Project::with('expenseItems')->orderBy('name')->get();
        return view('admin.clients.show', compact('client', 'products', 'projects'));
    }

    public function edit(Client $client): View
    {
        $client->load('customValues.customField');
        $customFields = CustomField::active()->ordered()->get();
        return view('admin.clients.edit', compact('client', 'customFields'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $old = $client->toArray();
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'email' => 'required|email|unique:clients,email,' . $client->id,
            'phone' => 'required|string|max:50',
            'telegram_id' => 'nullable|string|regex:/^\d+$/',
            'telegram_username' => 'nullable|string|max:100',
            'cabinet_password' => 'nullable|string|min:4|max:255',
            'registered_at' => 'required|date',
            'status' => 'in:active,inactive',
        ]);
        $validated['telegram_id'] = $request->filled('telegram_id') ? (int) $request->telegram_id : null;
        $validated['telegram_username'] = $request->filled('telegram_username') ? preg_replace('/^@/', '', trim($request->telegram_username)) : null;
        unset($validated['cabinet_password']);
        $client->update($validated);
        if ($request->filled('cabinet_password')) {
            $client->update(['cabinet_password' => Hash::make($request->cabinet_password)]);
        }
        $this->saveCustomValues($client, $request);
        $this->logActivity('updated', $client, $old, $client->fresh()->toArray());

        return redirect()->route('admin.clients.show', $client)->with('success', 'Данные сохранены.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();
        $this->logActivity('deleted', $client, $client->toArray(), null);
        return redirect()->route('admin.clients.index')->with('success', 'Клиент удалён.');
    }

    public function balance(Request $request, Client $client): RedirectResponse
    {
        $operationType = trim((string) $request->get('operation_type', ''));
        if ($operationType === '' && $request->has('type')) {
            $operationType = $request->get('type') === 'deposit' ? 'other_income' : 'loan_repayment';
            $request->merge(['operation_type' => $operationType]);
        } else {
            $request->merge(['operation_type' => $operationType ?: null]);
        }

        $rules = [
            'operation_type' => 'required|in:loan,loan_repayment,other_income,project_expense',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string|max:500',
            'product_id' => 'nullable|exists:products,id',
            'project_id' => 'nullable|exists:projects,id',
            'project_expense_item_id' => 'nullable|exists:project_expense_items,id',
        ];
        if ($operationType === BalanceTransaction::OPERATION_PROJECT_EXPENSE) {
            $rules['project_id'] = 'required|exists:projects,id';
            $rules['project_expense_item_id'] = 'required|exists:project_expense_items,id';
        }
        if ($operationType === BalanceTransaction::OPERATION_LOAN) {
            $rules['loan_days'] = 'required|integer|min:1|max:3650';
        } else {
            $rules['loan_days'] = 'nullable|integer|min:0';
        }

        $messages = [
            'operation_type.required' => 'Выберите тип операции.',
            'operation_type.in' => 'Недопустимый тип операции.',
            'project_id.required' => 'Выберите проект.',
            'project_expense_item_id.required' => 'Выберите статью расхода.',
            'loan_days.required' => 'Укажите количество дней займа.',
            'loan_days.min' => 'Количество дней займа должно быть не менее 1.',
        ];

        $validated = $request->validate($rules, $messages);

        $amount = (float) $validated['amount'];
        $operationType = $validated['operation_type'];
        // Займ и расход на проект — списание; возврат займа и прочие поступления — пополнение
        $type = in_array($operationType, [BalanceTransaction::OPERATION_LOAN, BalanceTransaction::OPERATION_PROJECT_EXPENSE], true) ? 'withdraw' : 'deposit';

        DB::transaction(function () use ($client, $validated, $amount, $operationType, $type) {
            if ($type === 'deposit') {
                $client->increment('balance', $amount);
            } else {
                $client->decrement('balance', $amount);
            }
            $client->refresh();

            $loanDueAt = null;
            if ($operationType === BalanceTransaction::OPERATION_LOAN && !empty($validated['loan_days'])) {
                $loanDueAt = now()->addDays((int) $validated['loan_days'])->startOfDay();
            }
            $transaction = BalanceTransaction::create([
                'client_id' => $client->id,
                'product_id' => ($operationType === BalanceTransaction::OPERATION_LOAN && !empty($validated['product_id'])) ? $validated['product_id'] : null,
                'project_id' => ($operationType === BalanceTransaction::OPERATION_PROJECT_EXPENSE && !empty($validated['project_id'])) ? $validated['project_id'] : null,
                'project_expense_item_id' => ($operationType === BalanceTransaction::OPERATION_PROJECT_EXPENSE && !empty($validated['project_expense_item_id'])) ? $validated['project_expense_item_id'] : null,
                'type' => $type,
                'operation_type' => $operationType,
                'loan_days' => $validated['loan_days'] ?? null,
                'loan_due_at' => $loanDueAt,
                'amount' => $amount,
                'balance_after' => $client->balance,
                'comment' => $validated['comment'] ?? null,
                'user_id' => auth()->id(),
            ]);
            $transaction->load(['client', 'product']);
            TelegramService::notifyTransaction($transaction);
            PushNotificationService::sendTransactionNotification($transaction);
        });

        return back()->with('success', 'Операция выполнена.');
    }

    public function uploadPhoto(Request $request, Client $client): RedirectResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120', // 5MB
        ]);

        if ($client->photo_path) {
            Storage::disk('public')->delete($client->photo_path);
        }

        $path = $request->file('photo')->store('clients', 'public');
        $client->update(['photo_path' => $path]);
        return back()->with('success', 'Фото загружено.');
    }

    public function deletePhoto(Client $client): RedirectResponse
    {
        if ($client->photo_path) {
            Storage::disk('public')->delete($client->photo_path);
            $client->update(['photo_path' => null]);
        }
        return back()->with('success', 'Фото удалено.');
    }

    private function saveCustomValues(Client $client, Request $request): void
    {
        $customFields = CustomField::active()->get();
        foreach ($customFields as $field) {
            $value = $request->input('custom_' . $field->name);
            if ($field->type === 'checkbox') {
                $value = $request->boolean('custom_' . $field->name) ? '1' : '0';
            }
            $client->setCustomFieldValue($field->name, $value);
        }
    }

    private function logActivity(string $action, $model, ?array $old, ?array $new): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
        ]);
    }
}
