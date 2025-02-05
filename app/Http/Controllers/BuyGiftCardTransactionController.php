<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\BuyGiftCardTransaction;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreBuyGiftCardTransactionRequest;
use App\Http\Requests\UpdateBuyGiftCardTransactionRequest;
use App\Models\Config;

class BuyGiftCardTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function countries()
    {

        // return response([], 200);
        $token = Config::where("name", "reloadly_key")->first()->value;

        $response = Http::withToken($token)->get(env("RELOADLY_AUDIENCE") . "/countries");

        $json_response = $response->json();
        $available_countries = ["US", "NG", "CA", "UK", "GB"];
        $filtered_countries = [];



        if ($response->status()  == 200) {
            foreach ($json_response as $data) {
                if (in_array($data['isoName'], $available_countries)) {
                    $filtered_countries[] = $data;
                }
            }
            return response($json_response, $response->status());
            // return response($filtered_countries, $response->status());
        } else {
            return response([
                "error" => true,
                "message" => "Buy Giftcard Internal Server Error. Please try again in a bit.",
            ], 500);
        }
    }

    public function kdcChargeAmount()
    {


        return response([
            "error" => false,
            "message" => "Buy Giftcard Rate Gotten.",
            "amount"  => 100.00
        ], 200);
    }



    public function cardsByCountry($iso)
    {

        $token = Config::where("name", "reloadly_key")->first()->value;
        $response = Http::withToken($token)->get(env("RELOADLY_AUDIENCE") . "/countries" . "/" . $iso . "/products");

        $json_response = $response->json();

        if ($response->status()  == 200) {
            return response($json_response, $response->status());
        } else {
            return response([
                "error" => true,
                "message" => "Buy Giftcard Internal Server Error. Please try again in a bit.",
            ], 500);
        }
    }

    public function makeOrder(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'productId' => 'required',
            'quantity' => 'required',
            'recipientCurrencyCode' => 'required',
            'senderFee' => 'required',
            'ngnAmount' => 'required',
            'giftCardAmount' => 'required',
            'unitPrice' => 'required',
            'card_logo' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors(), "message" => json_encode($validator->errors())], 401);
        }

        $ref = Carbon::now()->addHours(1)->format('YmdHis');

        $user = auth('api')->user();
        $wallet = $user->wallet;

        // $calc_total = ($request->unitPrice + $request->senderFee + 100) * $request->quantity;

        // check if user has enough money
        if ($wallet->balance < $request->ngnAmount) {
            return response()->json(['message' => 'Insufficient funds'], 401);
        }

        // check if the amount is greater than the minimum amount of 500
        if ($request->ngnAmount < 500) {
            return response()->json(['message' => 'Minimum amount is 500'], 401);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }


        $token = Config::where("name", "reloadly_key")->first()->value;
        // ->withToken($token)

        $response = Http::withHeaders(['Accept' => 'application/com.reloadly.giftcards-v1+json', 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token])->post(env("RELOADLY_AUDIENCE") . "/orders", [
            "productId" => $request->productId,
            "countryCode" => $user->country,
            "quantity" => $request->quantity,
            "unitPrice" => $request->unitPrice,
            "customIdentifier" => $ref,
            "senderName" => "Faveremit",
            "recipientEmail" => $user->email,
            "recipientPhoneDetails" =>
            [
                "countryCode" => $user->country,
                "phoneNumber" => $user->phone
            ]

        ]);

        $server_output = json_decode($response);


        $json_response = $response->json();
        Log::info($json_response);

        if ($response->status()  == 200) {
            $wallet->update([
                "balance" => $wallet->balance - $request->ngnAmount
            ]);

            $trx =  BuyGiftCardTransaction::create([
                'reloadly_transaction_id' => $json_response["transactionId"],
                'amount'  => $request->ngnAmount,
                'user_currency_code' => $json_response["currencyCode"],
                'reloadly_fee' => $json_response["fee"],
                'kdc_fee' => 100,
                'sms_fee' => $json_response["smsFee"],
                'reloadly_total' => $json_response["amount"],
                'recipient_email' => $json_response["recipientEmail"],
                'recipient_phone' => $json_response["recipientPhone"],
                'custom_identifier' => $json_response["customIdentifier"],
                'product_name' => $json_response["product"]["productName"],
                'status' => $json_response["status"],
                'productId' => $json_response["product"]["productId"],
                'quantity' => $json_response["product"]["quantity"],
                'unit_price' => $json_response["product"]["unitPrice"],
                'product_currency_code' => $json_response["product"]["currencyCode"],
                'brand_id' => $json_response["product"]["brand"]["brandId"],
                'brand_name' => $json_response["product"]["brand"]["brandName"],
                'card_logo' => $request->card_logo,
                "user_id" => $user->id,
            ]);
            return response([
                "message" => "transaction Successful",
                "error" => false,
                "transaction" => $trx
            ], 200);
        } else {
            Log::error($json_response);
            return response([
                "error" => true,
                "message" => "Buy Giftcard Internal Server Error. Please try again in a bit.",
            ], 500);
        }
    }

    public function getTransaction($id)
    {
        // $data = GiftCardTransaction::get();
        // $service = Service::where('id', 1)->first();
        $user = auth('api')->user();
        $giftcardTransaction = BuyGiftCardTransaction::where('id', $id)->first();

        $data = [
            'message' => 'Transaction fetched successfully',
            'transaction' => $giftcardTransaction,
        ];
        return response($data, 200);
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreBuyGiftCardTransactionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBuyGiftCardTransactionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BuyGiftCardTransaction  $buyGiftCardTransaction
     * @return \Illuminate\Http\Response
     */
    public function show($buyGiftCardTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BuyGiftCardTransaction  $buyGiftCardTransaction
     * @return \Illuminate\Http\Response
     */
    public function edit(BuyGiftCardTransaction $buyGiftCardTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateBuyGiftCardTransactionRequest  $request
     * @param  \App\Models\BuyGiftCardTransaction  $buyGiftCardTransaction
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBuyGiftCardTransactionRequest $request, BuyGiftCardTransaction $buyGiftCardTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BuyGiftCardTransaction  $buyGiftCardTransaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(BuyGiftCardTransaction $buyGiftCardTransaction)
    {
        //
    }
}
