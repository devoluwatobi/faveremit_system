<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Config;
use App\Models\BuyCryptoTransaction;
use Illuminate\Support\Facades\Validator;
use App\Services\FCMService;
use Illuminate\Http\Request;
use App\Models\Crypto;
use App\Models\Wallet;

class BuyCryptoTransactionController extends Controller
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'address' => 'required',
            'usd_amount' => 'required',
            'ngn_amount' => 'required',
            'usd_rate' => 'required',
            'crypto_id' => 'required',
            'crypto_amount' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        
        $crypto = Crypto::where("id", $request->crypto_id)->first();
        $user_wallet = Wallet::where("user_id", $user->id)->first();
        
        if($user_wallet->balance < $request->ngn_amount){
            return response(['errors' => "insufficient balance", 'message' => "insufficient balance", ], 422);
        }
        if($request->usd_amount < 5){
            return response(['errors' => "amount too low", 'message' => "minimum amount is 5 dollars", ], 422);
        }
        
        
        
       $trx =  BuyCryptoTransaction::create([
            "user_id" => $user->id,
            "address" => $request->address,
            "usd_amount" => $request->usd_amount,
            "ngn_amount" => $request->ngn_amount,
            "crypto_id" => $request->crypto_id,
            "crypto_symbol" => $crypto->short_code,
            "usd_rate" => $request->usd_rate,
            "crypto_amount" => $request->crypto_amount,
            ]);
            
        $user_wallet->update([
            "balance" => $user_wallet->balance - $request->ngn_amount
            ]);
            
            return response(['trx' => $trx, 'message' => "transaction created", ], 200);
            
    }
    
    public function getTransaction($id)
    {
        $user = auth('api')->user();
        $transaction = BuyCryptoTransaction::where('id', $id)->first();
        $crypto = Crypto::where('id', $transaction->crypto_id)->first();
        $transItem = [];
        // Status String
        switch ($transaction->status) {
            case 0:
                $status = 'Pending';
                break;
            case 1:
                $status = 'Completed';
                break;
            case 2:
                $status = 'Failed';
                break;
            case 3:
                $status = 'Cancelled';
                break;

            default:
                $status = 'Pending';
                break;
        }

        // $transItem['id'] = $transaction->id;
        // $transItem['user_id'] = $transaction->user_id;
        // $transItem['icon'] =  env('APP_URL') . $crypto->transaction_icon;
        // $transItem['service_id'] = 2;
        // $transItem['crypto_name'] = $crypto->name;
        // $transItem['crypto_short_code'] = $crypto->short_code;
        // $transItem['wallet_type'] = $wallet_type->name;
        // $transItem['btc_address'] = CryptoWalletAddress::withTrashed()->where('id', $transaction->crypto_wallet_address_id)->first()->address;
        // $transItem['usd_amount'] = $transaction->usd_amount;
        // $transItem['crypto_amount'] = $transaction->crypto_amount;
        // $transItem['usd_rate'] = $transaction->usd_rate;
        // $transItem['ngn_amount'] = $transaction->ngn_amount;
        // $transItem['transaction_ref'] = $transaction->id;
        // $transItem['status'] = $status;
        // $transItem['proof'] = env('APP_URL')  . $transaction->proof;
        // $transItem['rejected_reason'] = $transaction->rejected_reason;
        // $transItem['created_at'] = $transaction->created_at;
        
        $transaction->status = $status;
        $transaction->crypto_name = $crypto->name;
        $transaction->crypto_short_code = $crypto->crypto_short_code;


        $data = [
            'message' => 'Transaction fetched successfully',
            'transaction' => $transaction,
        ];
        return response($data, 200);
    }
    
    public function approveTransaction(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $cryptoTransaction = BuyCryptoTransaction::where('id', $request->id)->first();
        $crypto = Crypto::where('id', $cryptoTransaction->crypto_id)->first();
        if ($cryptoTransaction->status > 0) {
            return response(['errors' => ['Transaction status  cannot be updated.']], 422);
        }
        $form = [
            'status' => 1,
            'approved_by' => $user->id,
            'rejected_by' => null,
        ];

        $trxUser = User::where('id', $cryptoTransaction->user_id)->first();

        BuyCryptoTransaction::where('id', $cryptoTransaction->id)->update($form);
        $response = ["message" => 'Transaction approved'];

    

        try {
            FCMService::send(
                $trxUser->fcm,
                [
                    'title' => 'Crypto Purchase Successfull',
                    'body' => "Your Buy Crypto transaction with the reference " . $cryptoTransaction->id . " was successfull. Please check on the app for more detail.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }




        return response($response, 200);
    }
    
     public function rejectTransaction(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'rejected_reason' => 'required'
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $btcTransaction = BuyCryptoTransaction::where('id', $request->id)->first();
        $crypto = Crypto::where('id', $btcTransaction->crypto_id)->first();
        if ($btcTransaction->status > 0) {
            return response(['errors' => ['Transaction status cannot be updated.']], 422);
        }
        $form = [
            'status' => 2,
            'rejected_by' => $user->id,
            'approved_by' => null,
            'rejected_reason' => $request->rejected_reason,
        ];

        $trxUser = User::where('id', $btcTransaction->user_id)->first();
        BuyCryptoTransaction::where('id', $btcTransaction->id)->update($form);
        $response = ["message" => 'Transaction rejected'];
        
        $wallet = $trxUser->Wallet()->select('balance')->first();

        $walletForm =  [
            'balance' => $wallet->balance + $btcTransaction->ngn_amount,
        ];

        $trxUser->Wallet()->update($walletForm);

        try {
            FCMService::send(
                $trxUser->fcm,
                [
                    'title' => 'Buy Crypto Transaction Failed',
                    'body' => "Your buy " . $crypto->name . " transaction with the reference " . $btcTransaction->id . " has failed unfortunately. Please check on the app for more detail.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }


        return response($response, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BuyCryptoTransaction  $buyCryptoTransaction
     * @return \Illuminate\Http\Response
     */
    public function show(BuyCryptoTransaction $buyCryptoTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BuyCryptoTransaction  $buyCryptoTransaction
     * @return \Illuminate\Http\Response
     */
    public function edit(BuyCryptoTransaction $buyCryptoTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BuyCryptoTransaction  $buyCryptoTransaction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BuyCryptoTransaction $buyCryptoTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BuyCryptoTransaction  $buyCryptoTransaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(BuyCryptoTransaction $buyCryptoTransaction)
    {
        //
    }
}
