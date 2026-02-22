<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $query = Client::query()->with('customValues.customField');

        // Поиск по имени/фамилии
        if ($search = $request->get('search')) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term);
            });
        }

        // Фильтр по статусу
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Фильтры по пользовательским полям (из query string)
        $customFields = CustomField::active()->ordered()->get();
        foreach ($customFields as $field) {
            $value = $request->get('filter_' . $field->name);
            if ($value !== null && $value !== '') {
                $query->whereHas('customValues', function ($q) use ($field, $value) {
                    $q->where('custom_field_id', $field->id)->where('value', 'like', '%' . $value . '%');
                });
            }
        }

        // Сортировка
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowedSort = ['first_name', 'last_name', 'email', 'balance', 'registered_at', 'created_at'];
        if (in_array($sortField, $allowedSort)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $clients = $query->paginate(20)->withQueryString();
        return view('frontend.clients.index', compact('clients', 'customFields'));
    }

    public function show(Client $client): View
    {
        $client->load('customValues.customField', 'balanceTransactions');
        return view('frontend.clients.show', compact('client'));
    }
}
