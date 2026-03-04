<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BalanceTransaction;
use App\Models\Client;
use App\Models\ClientProjectInvestment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $loansTotal = (float) BalanceTransaction::where('operation_type', BalanceTransaction::OPERATION_LOAN)->sum('amount');
        $repaymentsTotal = (float) BalanceTransaction::where('operation_type', BalanceTransaction::OPERATION_LOAN_REPAYMENT)->sum('amount');
        $balanceTotal = round($loansTotal - $repaymentsTotal, 2);

        $stats = [
            'clients_total' => Client::count(),
            'clients_active' => Client::active()->count(),
            'balance_total' => $balanceTotal,
            'products_balance' => Product::sum('estimated_cost'),
            'products_count' => Product::count(),
        ];

        $recentClients = Client::latest()->take(5)->get();
        $clientIds = $recentClients->pluck('id')->all();
        if ($clientIds !== []) {
            $loansByClient = BalanceTransaction::whereIn('client_id', $clientIds)
                ->where('operation_type', BalanceTransaction::OPERATION_LOAN)
                ->selectRaw('client_id, SUM(amount) as total')
                ->groupBy('client_id')
                ->pluck('total', 'client_id');
            $repaymentsByClient = BalanceTransaction::whereIn('client_id', $clientIds)
                ->where('operation_type', BalanceTransaction::OPERATION_LOAN_REPAYMENT)
                ->selectRaw('client_id, SUM(amount) as total')
                ->groupBy('client_id')
                ->pluck('total', 'client_id');
            foreach ($recentClients as $c) {
                $loans = (float) ($loansByClient[$c->id] ?? 0);
                $repayments = (float) ($repaymentsByClient[$c->id] ?? 0);
                $c->loan_balance = round($loans - $repayments, 2);
            }
        }

        // Займы клиентов: все клиенты с ненулевым остатком займа или с историей займов
        $loanClientIds = BalanceTransaction::whereIn('operation_type', [
            BalanceTransaction::OPERATION_LOAN,
            BalanceTransaction::OPERATION_LOAN_REPAYMENT,
        ])->distinct()->pluck('client_id')->filter()->values()->all();

        $loansByClientList = collect();
        if ($loanClientIds !== []) {
            $clientsWithLoans = Client::whereIn('id', $loanClientIds)->orderBy('first_name')->orderBy('last_name')->get();
            $loansSum = BalanceTransaction::whereIn('client_id', $loanClientIds)
                ->where('operation_type', BalanceTransaction::OPERATION_LOAN)
                ->selectRaw('client_id, SUM(amount) as total')
                ->groupBy('client_id')
                ->pluck('total', 'client_id');
            $repaymentsSum = BalanceTransaction::whereIn('client_id', $loanClientIds)
                ->where('operation_type', BalanceTransaction::OPERATION_LOAN_REPAYMENT)
                ->selectRaw('client_id, SUM(amount) as total')
                ->groupBy('client_id')
                ->pluck('total', 'client_id');
            $transactionsByClient = BalanceTransaction::whereIn('client_id', $loanClientIds)
                ->whereIn('operation_type', [BalanceTransaction::OPERATION_LOAN, BalanceTransaction::OPERATION_LOAN_REPAYMENT])
                ->with(['product', 'project', 'projectExpenseItem'])
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('client_id');

            foreach ($clientsWithLoans as $c) {
                $loans = (float) ($loansSum[$c->id] ?? 0);
                $repayments = (float) ($repaymentsSum[$c->id] ?? 0);
                $balance = round($loans - $repayments, 2);
                $transactions = $transactionsByClient->get($c->id, collect());
                $loansByClientList->push([
                    'client' => $c,
                    'amount' => $balance,
                    'transactions' => $transactions,
                ]);
            }
            $loansByClientList = $loansByClientList->sortByDesc('amount')->values();
        }

        // Вложения в проект по клиентам (ClientProjectInvestment)
        $investmentClientIds = ClientProjectInvestment::distinct()->pluck('client_id')->filter()->values()->all();
        $investmentsByClientList = collect();
        if ($investmentClientIds !== []) {
            $clientsWithInvestments = Client::whereIn('id', $investmentClientIds)->orderBy('first_name')->orderBy('last_name')->get();
            $totalByClient = ClientProjectInvestment::whereIn('client_id', $investmentClientIds)
                ->selectRaw('client_id, SUM(amount) as total')
                ->groupBy('client_id')
                ->pluck('total', 'client_id');
            $recordsByClient = ClientProjectInvestment::whereIn('client_id', $investmentClientIds)
                ->with('project')
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('client_id');

            foreach ($clientsWithInvestments as $c) {
                $total = round((float) ($totalByClient[$c->id] ?? 0), 2);
                $records = $recordsByClient->get($c->id, collect());
                $investmentsByClientList->push([
                    'client' => $c,
                    'amount' => $total,
                    'records' => $records,
                ]);
            }
            $investmentsByClientList = $investmentsByClientList->sortByDesc('amount')->values();
        }

        // Расходы на проект по клиентам (из транзакций — OPERATION_PROJECT_EXPENSE)
        $expenseClientIds = BalanceTransaction::where('operation_type', BalanceTransaction::OPERATION_PROJECT_EXPENSE)
            ->distinct()
            ->pluck('client_id')
            ->filter()
            ->values()
            ->all();

        $expensesByClientList = collect();
        if ($expenseClientIds !== []) {
            $clientsWithExpenses = Client::whereIn('id', $expenseClientIds)->orderBy('first_name')->orderBy('last_name')->get();
            $totalByClient = BalanceTransaction::whereIn('client_id', $expenseClientIds)
                ->where('operation_type', BalanceTransaction::OPERATION_PROJECT_EXPENSE)
                ->selectRaw('client_id, SUM(amount) as total')
                ->groupBy('client_id')
                ->pluck('total', 'client_id');
            $transactionsByClient = BalanceTransaction::whereIn('client_id', $expenseClientIds)
                ->where('operation_type', BalanceTransaction::OPERATION_PROJECT_EXPENSE)
                ->with(['project', 'projectExpenseItem'])
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('client_id');

            foreach ($clientsWithExpenses as $c) {
                $total = round((float) ($totalByClient[$c->id] ?? 0), 2);
                $transactions = $transactionsByClient->get($c->id, collect());
                $expensesByClientList->push([
                    'client' => $c,
                    'amount' => $total,
                    'transactions' => $transactions,
                ]);
            }
            $expensesByClientList = $expensesByClientList->sortByDesc('amount')->values();
        }

        return view('admin.dashboard', compact('stats', 'recentClients', 'loansByClientList', 'investmentsByClientList', 'expensesByClientList'));
    }

    public function activity(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->latest()
            ->paginate(50);
        return view('admin.activity', compact('logs'));
    }
}
