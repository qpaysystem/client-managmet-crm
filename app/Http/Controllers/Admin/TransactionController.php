<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceTransaction;
use App\Models\Client;
use App\Models\Product;
use App\Models\Project;
use App\Services\PushNotificationService;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = BalanceTransaction::query()
            ->with(['client', 'user', 'product', 'project', 'projectExpenseItem'])
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }
        if ($request->filled('operation_type')) {
            $query->where('operation_type', $request->get('operation_type'));
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->get('client_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $transactions = $query->paginate(30)->withQueryString();

        $clients = Client::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.transactions.index', compact('transactions', 'clients'));
    }

    public function create(): View
    {
        $clients = Client::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        $products = Product::orderBy('name')->get(['id', 'name']);
        $projects = Project::with('expenseItems')->orderBy('name')->get();
        return view('admin.transactions.create', compact('clients', 'products', 'projects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $operationType = trim((string) $request->get('operation_type', ''));
        $request->merge(['operation_type' => $operationType ?: null]);

        $rules = [
            'transaction_date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
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
            'transaction_date.required' => 'Укажите дату транзакции.',
            'client_id.required' => 'Выберите клиента.',
            'operation_type.required' => 'Выберите тип операции.',
            'project_id.required' => 'Выберите проект.',
            'project_expense_item_id.required' => 'Выберите статью расхода.',
            'loan_days.required' => 'Укажите количество дней займа.',
        ];

        $validated = $request->validate($rules, $messages);

        $client = Client::findOrFail($validated['client_id']);
        $amount = (float) $validated['amount'];
        $operationType = $validated['operation_type'];
        $type = in_array($operationType, [BalanceTransaction::OPERATION_LOAN, BalanceTransaction::OPERATION_PROJECT_EXPENSE], true) ? 'withdraw' : 'deposit';
        $transactionDate = \Carbon\Carbon::parse($validated['transaction_date']);

        DB::transaction(function () use ($client, $validated, $amount, $operationType, $type, $transactionDate) {
            if ($type === 'deposit') {
                $client->increment('balance', $amount);
            } else {
                $client->decrement('balance', $amount);
            }
            $client->refresh();

            $loanDueAt = null;
            if ($operationType === BalanceTransaction::OPERATION_LOAN && !empty($validated['loan_days'])) {
                $loanDueAt = $transactionDate->copy()->addDays((int) $validated['loan_days'])->startOfDay();
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
                'created_at' => $transactionDate,
            ]);
            $transaction->load(['client', 'product']);
            TelegramService::notifyTransaction($transaction);
            PushNotificationService::sendTransactionNotification($transaction);
        });

        return redirect()->route('admin.transactions.index')->with('success', 'Транзакция создана.');
    }

    public function destroy(BalanceTransaction $transaction): RedirectResponse
    {
        $client = $transaction->client;
        $amount = (float) $transaction->amount;

        DB::transaction(function () use ($transaction, $client, $amount) {
            if ($transaction->type === 'deposit') {
                $client->decrement('balance', $amount);
            } else {
                $client->increment('balance', $amount);
            }
            $transaction->delete();
        });

        return redirect()->route('admin.transactions.index')->with('success', 'Транзакция удалена. Баланс клиента обновлён.');
    }
}
