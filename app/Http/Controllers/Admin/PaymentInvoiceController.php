<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentInvoice;
use App\Models\Project;
use App\Models\ProjectExpenseItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = PaymentInvoice::query()
            ->with(['responsibleUser', 'project', 'projectExpenseItem'])
            ->orderByRaw("FIELD(priority, ?, ?, ?) ASC", [
                PaymentInvoice::PRIORITY_URGENT,
                PaymentInvoice::PRIORITY_IMMEDIATE,
                PaymentInvoice::PRIORITY_PLANNED,
            ])
            ->orderByRaw('due_date is null asc')
            ->orderBy('due_date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
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
        $projects = Project::orderBy('name')->get(['id', 'name']);

        return view('admin.payment-invoices.index', compact('invoices', 'users', 'projects'));
    }

    public function create(): View
    {
        $users = User::orderBy('name')->get(['id', 'name']);
        $projects = Project::orderBy('name')->get(['id', 'name']);

        $selectedProjectId = request()->integer('project_id') ?: (int) old('project_id');
        $expenseItems = $selectedProjectId
            ? ProjectExpenseItem::query()
                ->where('project_id', $selectedProjectId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'project_id'])
            : collect();

        return view('admin.payment-invoices.create', compact('users', 'projects', 'expenseItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        $expenseItem = ProjectExpenseItem::query()
            ->where('id', $validated['project_expense_item_id'])
            ->first();
        $validated['expense_article'] = $expenseItem?->name ?? $validated['expense_article'] ?? '—';

        $validated['responsible_user_id'] = $validated['responsible_user_id'] ?? null;
        $validated['received_date'] = !empty($validated['received_date']) ? $validated['received_date'] : null;
        $validated['due_date'] = !empty($validated['due_date']) ? $validated['due_date'] : null;
        $validated['comments'] = $validated['comments'] ?? null;

        if (($validated['status'] ?? PaymentInvoice::STATUS_UNPAID) === PaymentInvoice::STATUS_PAID) {
            $validated['paid_at'] = $validated['paid_at'] ?? now();
        } else {
            $validated['paid_at'] = null;
        }

        PaymentInvoice::create($validated);

        return redirect()->route('admin.payment-invoices.index')->with('success', 'Счёт добавлен.');
    }

    public function edit(PaymentInvoice $paymentInvoice): View
    {
        $users = User::orderBy('name')->get(['id', 'name']);
        $projects = Project::orderBy('name')->get(['id', 'name']);

        $selectedProjectId = (int) old('project_id', $paymentInvoice->project_id);
        $expenseItems = $selectedProjectId
            ? ProjectExpenseItem::query()
                ->where('project_id', $selectedProjectId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'project_id'])
            : collect();
        return view('admin.payment-invoices.edit', [
            'invoice' => $paymentInvoice,
            'users' => $users,
            'projects' => $projects,
            'expenseItems' => $expenseItems,
        ]);
    }

    public function update(Request $request, PaymentInvoice $paymentInvoice): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        $expenseItem = ProjectExpenseItem::query()
            ->where('id', $validated['project_expense_item_id'])
            ->first();
        $validated['expense_article'] = $expenseItem?->name ?? $validated['expense_article'] ?? $paymentInvoice->expense_article;

        $validated['responsible_user_id'] = $validated['responsible_user_id'] ?? null;
        $validated['received_date'] = !empty($validated['received_date']) ? $validated['received_date'] : null;
        $validated['due_date'] = !empty($validated['due_date']) ? $validated['due_date'] : null;
        $validated['comments'] = $validated['comments'] ?? null;

        if (($validated['status'] ?? PaymentInvoice::STATUS_UNPAID) === PaymentInvoice::STATUS_PAID) {
            $validated['paid_at'] = $paymentInvoice->paid_at ?? now();
        } else {
            $validated['paid_at'] = null;
        }

        $paymentInvoice->update($validated);

        return redirect()->route('admin.payment-invoices.index')->with('success', 'Счёт обновлён.');
    }

    public function destroy(PaymentInvoice $paymentInvoice): RedirectResponse
    {
        $paymentInvoice->delete();
        return redirect()->route('admin.payment-invoices.index')->with('success', 'Счёт удалён.');
    }

    public function expenseItems(Request $request): JsonResponse
    {
        $projectId = $request->integer('project_id');
        if (!$projectId) {
            return response()->json(['items' => []]);
        }

        $items = ProjectExpenseItem::query()
            ->where('project_id', $projectId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name']);

        return response()->json([
            'items' => $items,
        ]);
    }

    private function validatePayload(Request $request): array
    {
        $projectId = $request->integer('project_id');

        return $request->validate([
            'amount' => 'required|numeric|min:0',
            'project_id' => 'required|exists:projects,id',
            'project_expense_item_id' => [
                'required',
                Rule::exists('project_expense_items', 'id')->where(function ($q) use ($projectId) {
                    if ($projectId) {
                        $q->where('project_id', $projectId);
                    }
                }),
            ],
            'responsible_user_id' => 'nullable|exists:users,id',
            'received_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:received_date',
            'comments' => 'nullable|string|max:5000',
            'priority' => 'required|in:' . implode(',', array_keys(PaymentInvoice::priorityLabels())),
            'status' => 'required|in:' . implode(',', array_keys(PaymentInvoice::statusLabels())),
        ]);
    }
}

