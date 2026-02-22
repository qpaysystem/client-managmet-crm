<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceTransaction;
use App\Models\Client;
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
