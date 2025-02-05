<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Wallet;
use App\Models\RewardWallet;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\RewardWalletTransaction;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreRewardWalletRequest;
use App\Http\Requests\UpdateRewardWalletRequest;

class RewardWalletController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth('api')->user();

        // Fetch user's wallet
        $wallet = RewardWallet::where("user_id", $user->id)->first();

        // Handle case where no wallet exists
        $balance = $wallet ? $wallet->balance : 0;

        // Fetch referrals with counts for pending and successful status
        $refs = RewardWalletTransaction::where("user_id", $user->id)
            ->where("type", "referral")
            ->get();

        $pending_refs_count = $refs->where("status", 0)->count();
        $completed_refs_count = $refs->where("status", 1)->count();

        return response([
            "balance" => $balance,
            "total_ref" => $refs->count(),
            "pending_ref" => $pending_refs_count,
            "successful_ref" => $completed_refs_count,
            "referrals" => $refs,
        ]);
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
     * @param  \App\Http\Requests\StoreRewardWalletRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRewardWalletRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RewardWallet  $rewardWallet
     * @return \Illuminate\Http\Response
     */
    public function show(RewardWallet $rewardWallet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RewardWallet  $rewardWallet
     * @return \Illuminate\Http\Response
     */
    public function edit(RewardWallet $rewardWallet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRewardWalletRequest  $request
     * @param  \App\Models\RewardWallet  $rewardWallet
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRewardWalletRequest $request, RewardWallet $rewardWallet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RewardWallet  $rewardWallet
     * @return \Illuminate\Http\Response
     */
    public function destroy(RewardWallet $rewardWallet)
    {
        //
    }

    public function claim(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $reward_wallet = RewardWallet::where("user_id", $user->id)->first();

        // check if user has bank details

        // check if the user has enough money to withdraw
        if ($reward_wallet->balance < $request->amount) {
            return response(['errors' => ['You do not have enough points in your wallet to withdraw']], 422);
        }

        // check if the balance is within the range of the minimum and maximum withdrawal amount
        // if ($request->amount < 20 || $request->amount > 100) {
        //     return response(['errors' => ['The amount you are trying to withdraw is not within the range of the minimum and maximum withdrawal amount']], 422);
        // }

        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }


        $walletTransaction = new RewardWalletTransaction;
        $walletTransaction->user_id = $user->id;
        $walletTransaction->amount = $request->amount;
        $walletTransaction->status = 1;
        $walletTransaction->type = "withdrawal";

        $reward_wallet->balance = $reward_wallet->balance - $request->amount;
        $reward_wallet->save();
        $walletTransaction->save();

        $amount = number_format($request->amount, 2);

        // credit money wallet
        $user_wallet = Wallet::where("user_id", $user->id)->first();
        $user_wallet->balance = $user_wallet->balance + $request->amount;
        $user_wallet->save();

        try {
            FCMService::send(
                $user->fcm,
                [
                    'title' => 'Claimed Reward',
                    'body' => "You just claimed a reward cash of NGN" . $request->amount,
                ]
            );
        } catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }


        //send a funds confirmation email to the user
        // \Mail::to($user->email)->send(new \App\Mail\WithdrawWallet($user, $amount));


        // create form with the transaction reference id, amount and status
        $form = [
            'user_id' => $user->id,
            'amount' => $request->amount,
            'status' => 0,
            'transaction_ref' => $walletTransaction->id,
        ];

        // return response with form data and message
        return response([
            'message' => 'Cash Reward withdrawn successfully',
            'data' => $form,
        ], 200);
    }
}
