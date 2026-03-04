<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BalanceTransaction;
use App\Models\Client;
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

        return view('admin.dashboard', compact('stats', 'recentClients'));
    }

    public function activity(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->latest()
            ->paginate(50);
        return view('admin.activity', compact('logs'));
    }
}
