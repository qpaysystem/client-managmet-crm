<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CustomField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Client::query()->with('customValues.customField');

        if ($search = $request->get('search')) {
            $query->search($search);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $clients = $query->latest()->paginate($perPage);

        return response()->json($clients);
    }

    public function show(Client $client): JsonResponse
    {
        $client->load('customValues.customField', 'balanceTransactions');
        return response()->json($client);
    }
}
