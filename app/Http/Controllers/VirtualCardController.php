<?php

namespace App\Http\Controllers;

use App\Services\VirtualCardService;
use Illuminate\Http\Request;

class VirtualCardController extends Controller
{
    protected $service;

    public function __construct(VirtualCardService $service)
    {
        $this->service = $service;
    }

    public function createCard(Request $request)
    {
        $payload = $request->validate([
            'name' => 'required|string',
            'currency' => 'required|string',
            'limit' => 'required|numeric',
        ]);

        return response()->json($this->service->createCard($payload));
    }

    public function fundCard(Request $request, $cardId)
    {
        $request->validate(['amount' => 'required|numeric']);
        return response()->json($this->service->fundCard($cardId, $request->amount));
    }

    public function reverseFunding($transactionId)
    {
        return response()->json($this->service->reverseFunding($transactionId));
    }

    public function getTransactions($cardId)
    {
        return response()->json($this->service->getTransactions($cardId));
    }

    public function handleWebhook(Request $request)
    {
        return $this->service->handleWebhook($request->all());
    }
}
