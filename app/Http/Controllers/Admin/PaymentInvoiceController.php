<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentInvoice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = PaymentInvoice::query()
            ->with(['responsibleUser'])
            ->orderByRaw("FIELD(priority, ?, ?, ?) ASC", [
                PaymentInvoice::PRIORITY_URGENT,
                PaymentInvoice::PRIORITY_IMMEDIATE,
                PaymentInvoice::PRIORITY_PLANNED,
            ])
            ->orderByRaw('due_date is null asc')
            ->orderBy('due_date')
            ->orderByDesc('id');

        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }
        if ($request->filled('responsible_user_id')) {
            $query->where('responsible_user_id', $request->integer('responsible_user_id'));
        }
        if ($request->filled('search')) {
            $term = '%' . $request->get('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('expense_article', 'like', $term)
                    ->orWhere('comments', 'like', $term);
            });
        }

        $invoices = $query->paginate(20)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('admin.payment-invoices.index', compact('invoices', 'users'));
    }

    public function create(): View
    {
        $users = User::orderBy('name')->get(['id', 'name']);
        return view('admin.payment-invoices.create', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        $validated['responsible_user_id'] = $validated['responsible_user_id'] ?? null;
        $validated['received_date'] = !empty($validated['received_date']) ? $validated['received_date'] : null;
        $validated['due_date'] = !empty($validated['due_date']) ? $validated['due_date'] : null;
        $validated['comments'] = $validated['comments'] ?? null;

        PaymentInvoice::create($validated);

        return redirect()->route('admin.payment-invoices.index')->with('success', 'Счёт добавлен.');
    }

    public function edit(PaymentInvoice $paymentInvoice): View
    {
        $users = User::orderBy('name')->get(['id', 'name']);
        return view('admin.payment-invoices.edit', [
            'invoice' => $paymentInvoice,
            'users' => $users,
        ]);
    }

    public function update(Request $request, PaymentInvoice $paymentInvoice): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        $validated['responsible_user_id'] = $validated['responsible_user_id'] ?? null;
        $validated['received_date'] = !empty($validated['received_date']) ? $validated['received_date'] : null;
        $validated['due_date'] = !empty($validated['due_date']) ? $validated['due_date'] : null;
        $validated['comments'] = $validated['comments'] ?? null;

        $paymentInvoice->update($validated);

        return redirect()->route('admin.payment-invoices.index')->with('success', 'Счёт обновлён.');
    }

    public function destroy(PaymentInvoice $paymentInvoice): RedirectResponse
    {
        $paymentInvoice->delete();
        return redirect()->route('admin.payment-invoices.index')->with('success', 'Счёт удалён.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'expense_article' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'responsible_user_id' => 'nullable|exists:users,id',
            'received_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:received_date',
            'comments' => 'nullable|string|max:5000',
            'priority' => 'required|in:' . implode(',', array_keys(PaymentInvoice::priorityLabels())),
        ]);
    }
}

