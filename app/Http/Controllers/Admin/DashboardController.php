<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'clients_total' => Client::count(),
            'clients_active' => Client::active()->count(),
            'balance_total' => Client::sum('balance'),
            'products_balance' => Product::sum('estimated_cost'),
            'products_count' => Product::count(),
        ];
        $recentClients = Client::latest()->take(5)->get();
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
