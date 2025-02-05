<?php

namespace App\Http\Controllers;

use Exception;
use DateTimeZone;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Wallet;
use App\Models\BankDetails;
use Facade\FlareClient\Api;
use App\Services\FCMService;
use Illuminate\Http\Request;
use App\Models\WalletTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreWalletTransactionsRequest;
use App\Http\Requests\UpdateWalletTransactionsRequest;
use App\Models\Config;
use App\Models\FundTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class WalletTransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $user = auth('api')->user();

        $pending = $user->walletTransaction()->Where('status', 0)->select('id', 'user_id', 'amount', 'status', 'created_at')->get();
        $rejected = $user->walletTransaction()->Where('status', 2)->select('id', 'user_id', 'amount', 'status', 'created_at')->get();
        $approved = $user->walletTransaction()->Where('status', 1)->select('id', 'user_id', 'amount', 'status', 'created_at')->get();
        $cancelled = $user->walletTransaction()->Where('status', 3)->select('id', 'user_id', 'amount', 'status', 'created_at')->get();

        $response = [
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'cancelled' => $cancelled,
        ];

        return response($response, 200);
    }

    public function showAll()
    {
        $user = auth('api')->user();

        $transactions = $user->walletTransaction()->select('id', 'user_id', 'amount', 'status', 'created_at')->get();


        return response($transactions, 200);
    }

    public function getTransaction($id)
    {
        $user = auth('api')->user();

        $transaction = WalletTransactions::where('id', $id)->first();


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
        $bankDetails = BankDetails::where('id', $transaction->bank_id)->first();
        if ((empty($transaction->bank) || empty($transaction->bank_name) || empty($transaction->account_number) || empty($transaction->account_name)) && $bankDetails != null) {
            $bank = [
                "bank" => $bankDetails->bank ?? 0,
                "bank_name" => $bankDetails->bank_name,
                "account_number" => $bankDetails->account_number,
                "account_name" => $bankDetails->account_name,
            ];
        } else {
            $bank = [
                "bank" => $transaction->bank,
                "bank_name" => $transaction->bank_name,
                "account_number" => $transaction->account_number,
                "account_name" => $transaction->account_name,
            ];
        }

        $transItem = [];
        $transItem['id'] = $transaction->id;
        $transItem['user_id'] = $transaction->user_id;
        $transItem['amount'] = $transaction->amount;
        $transItem['transaction_ref'] = $transaction->transaction_ref;
        $transItem['status'] = $status;
        $transItem['approved_by'] = $transaction->approved_by;
        $transItem['rejected_by'] = $transaction->rejected_by;
        $transItem['rejected_reason'] = $transaction->rejected_reason;
        $transItem['bank'] = $bank;
        $transItem['charge'] = $transaction->charge;

        $transItem['created_at'] = $transaction->created_at;
        $transItem['updated_at'] = $transaction->updated_at;
        $transItem['type'] = $transaction->type;


        $data = [
            'message' => 'Transaction fetched successfully',
            'transaction' => $transItem,
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
     * @param  \App\Http\Requests\StoreWalletTransactionsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();

        // get withdrawal charge
        $charge = Config::where("name", "withdrawal_charge")->first();
        $charge_amount = floatval($charge->value);

        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'bank_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        // get ballance


        $wallet = Wallet::where("user_id", $user->id)->first();
        $balance = $wallet->balance;
        if ($request->amount < 1000) {
            $response = ['message' => "Minimum withdrawal amount is ₦1000"];
        } elseif (($request->amount) > $balance) {
            $response = ['message' => "You can't withdraw above your balance"];
        } else {
            $create_data = $request->toArray();
            $create_data["charge"] = $charge_amount;
            $walletTransaction =   $user->walletTransaction()->create($create_data);
            $response = ['message' => "Withdraw successful"];
        }

        $amount = number_format($request->amount, 2);


        try {

            FCMService::sendToAdmins(
                [
                    'title' => 'New Withdrawal Request',
                    'body' => "There is a new withdrawal request of NGN" . $request->amount . " by a Faveremit user",
                ]
            );

            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $walletTransaction->created_at->setTimezone($wa_time);

            //send a funds confirmation email to the user
            Mail::to($user->email)->send(new \App\Mail\WithdrawWallet($user, $amount, number_format($wallet->balance, 2), date("h:iA, F jS, Y", strtotime("$time"))));
        } catch (\Exception $e) {
            info($e);
        }

        return response($response, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WalletTransactions  $walletTransactions
     * @return \Illuminate\Http\Response
     */
    public function show(WalletTransactions $walletTransactions)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WalletTransactions  $walletTransactions
     * @return \Illuminate\Http\Response
     */
    public function edit(WalletTransactions $walletTransactions)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateWalletTransactionsRequest  $request
     * @param  \App\Models\WalletTransactions  $walletTransactions
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateWalletTransactionsRequest $request, WalletTransactions $walletTransactions)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WalletTransactions  $walletTransactions
     * @return \Illuminate\Http\Response
     */
    public function destroy(WalletTransactions $walletTransactions)
    {
        //
    }

    //  create wallet withdrawal request
    public function withdraw(Request $request)
    {

        // get withdrawal charge
        $charge = Config::where("name", "withdrawal_charge")->first();
        $charge_amount = floatval($charge->value);

        // user
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $wallet = $user->wallet;

        // check if user has bank details
        $bankAccount = BankDetails::where('user_id', $user->id)->first();
        if (!$bankAccount) {
            return response(['message' => 'You have no bank details'], 400);
        }

        // check if the user has enough money to withdraw
        if ($wallet->balance < ($request->amount)) {
            return response(['message' => 'You do not have enough money in your wallet to withdraw'], 422);
        }

        // check if the balance is within the range of the minimum and maximum withdrawal amount
        if ($request->amount < 1000 || $request->amount > 5000000) {
            return response(['message' => 'The amount you are trying to withdraw is not within the range of the minimum and maximum withdrawal amount'], 422);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }


        $walletTransaction = new WalletTransactions;
        $walletTransaction->user_id = $user->id;
        $walletTransaction->amount = $request->amount;
        $walletTransaction->bank_id = $bankAccount->id;
        $walletTransaction->status = 0;
        $walletTransaction->bank = $bankAccount->bank;
        $walletTransaction->bank_name = $bankAccount->bank_name;
        $walletTransaction->account_number = $bankAccount->account_number;
        $walletTransaction->account_name = $bankAccount->account_name;
        $walletTransaction->charge = $charge_amount;

        $wallet->balance = $wallet->balance - ($request->amount);
        $wallet->save();

        // create unique transaction reference id
        $transaction_ref = uniqid();
        $walletTransaction->transaction_ref = $transaction_ref;
        $walletTransaction->save();

        $amount = number_format($request->amount, 2);

        // FCMService::sendToAdmins(
        //     [
        //         'title' => '😎 New Withdrawal Request',
        //         'body' => "There is a new withdrawal request of NGN" . $request->amount . " by a Faveremit user",
        //     ]
        // );

        try {

            FCMService::sendToAdmins(
                [
                    'title' => 'New Withdrawal Request',
                    'body' => "There is a new withdrawal request of NGN" . $request->amount . " by a Faveremit user",
                ]
            );

            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $walletTransaction->created_at->setTimezone($wa_time);

            //send a funds confirmation email to the user
            Mail::to($user->email)->send(new \App\Mail\WithdrawWallet($user, $amount, number_format($wallet->balance, 2), date("h:iA, F jS, Y", strtotime("$time"))));
        } catch (\Exception $e) {
            info($e);
        }

        // create form with the transaction reference id, amount and status
        $form = [
            'user_id' => $user->id,
            'amount' => $request->amount,
            'status' => 0,
            'transaction_ref' => $transaction_ref,
        ];

        // return response with form data and message
        return response([
            'message' => 'Wallet withdrawal request created successfully',
            'data' => $form,
        ], 200);
    }

    public function cancel(Request $request)
    {
        // validate id
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);


        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = auth('api')->user();
        $transaction = WalletTransactions::where('id', $request->id)->first();

        if (empty($transaction)) {
            return response(['errors' => ['Transaction not found']], 422);
        } else if ($transaction->status > 0) {
            return response(['errors' => ['Transaction cannot be cancelled.']], 422);
        }

        // save transaction status to cancelled
        WalletTransactions::where('id', $request->id)->first()->update([
            'status' => 3,
        ]);

        $wallet = Wallet::where("user_id", $user->id)->first();

        $walletForm =  [
            'balance' => $wallet->balance + ($transaction->amount),
        ];
        $wallet->update($walletForm);

        // remove charge
        WalletTransactions::where('id', $request->id)->first()->update([
            'charge' => 0,
        ]);


        try {
            FCMService::send(
                $user->fcm,
                [
                    'title' => 'Withdrawal Cancelled',
                    'body' => "Your Withdrawal request with the reference " . $transaction->transaction_ref . " was cancelled successfully.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }


        // return response with message and data
        return response(['message' => 'Transaction cancelled successfully'], 200);
    }

    public function accept(Request $request)
    {
        // validate id
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);


        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = auth('api')->user();
        $transaction = WalletTransactions::where('id', $request->id)->first();

        if (empty($transaction)) {
            return response(['message' => 'Withdrawal not found'], 422);
        } else if ($transaction->status > 0) {
            return response(['message' => 'Withdrawal status cannot be updated.'], 422);
        }
        
        
        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        $body  = [
            "bank_code" => $transaction->bank,
            "account_no" => $transaction->account_number,
            "amount" => $transaction->amount,
            "narration" => "Faveremit Withdrawal",
            "callback_url" => "https://api.devoluwatobi.com/api/red-trf-hook",
            "reference" => $transactionRef,
        ];
        $body["reference"] =  $transactionRef;
        
        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/2.6/payout/bank-transfer/create', $body);

        $server_output = json_decode($response);

        

        $data = $response->json();

        Log::info($data);
        
        if (
            $data['status'] == true && $data['response'] == 200
        ){

        // save transaction status to cancelled
        WalletTransactions::where('id', $request->id)->first()->update([
            'status' => $status == "approved" ? 1 : ($status == "pending" ? 1 : ($status == "cancelled" ? 2 : 1)),
            'approved_by' => $user->id,
            'trx_status' => $status,
            'rejected_by' => null,
        ]);

        $trxUser = User::where('id', $transaction->user_id)->first();
        // save transaction status to cancelled

        try {
            FCMService::send(
                $trxUser->fcm,
                [
                    'title' => '🎉 Withdrawal Completed',
                    'body' => "Your Withdrawal request with the reference " . $transaction->transaction_ref . " was completed successfully.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }


        $wa_time = new DateTimeZone('Africa/Lagos');
        $time = $transaction->created_at->setTimezone($wa_time);

        $emailData = [
            'name' => $trxUser->first_name,
            'amount' => number_format($transaction->amount, 2),
            'bank' => $transaction->bank_name,
            'account_number' =>  $transaction->account_number,
            'status' => 'withdrawal approved',
            'time' => date("h:iA, F jS, Y", strtotime("$time")),
        ];

        try {
            Mail::to($trxUser->email)->send(new \App\Mail\approvedWithdraw($emailData));
        } catch (\Exception $e) {
            info($e);
        }

        // return response with message and data
        return response(['message' => 'Withdrawal Approved successfully'], 200);}else {

            return response(['message' => 'Withdrawal Approval Failed'], 452);
        }
    }

    public function reject(Request $request)
    {
        // validate id
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'rejected_reason' => 'required',
        ]);


        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = auth('api')->user();
        $transaction = WalletTransactions::where('id', $request->id)->first();

        if (empty($transaction)) {
            return response(['errors' => ['Withdrawal not found']], 422);
        } else if ($transaction->status > 0) {
            return response(['errors' => ['Withdrawal status cannot be updated.']], 422);
        }


        // save transaction status to cancelled
        WalletTransactions::where('id', $request->id)->first()->update([
            'status' => 2,
            'rejected_reason' => $request->rejected_reason,
            'rejected_by' => $user->id,
            'approved_by' => null,
        ]);

        $trxUser = User::where('id', $transaction->user_id)->first();


        $wallet = $trxUser->Wallet()->select('balance')->first();

        $walletForm =  [
            'balance' => $wallet->balance + $transaction->amount,
        ];

        $trxUser->Wallet()->update($walletForm);

        try {
            FCMService::send(
                $trxUser->fcm,
                [
                    'title' => '❗️ Withdrawal Failed',
                    'body' => "Your Withdrawal request with the reference " . $transaction->transaction_ref . " has failed. Check app for more information.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }

        $wa_time = new DateTimeZone('Africa/Lagos');
        $time = $transaction->created_at->setTimezone($wa_time);


        $emailData = [
            'name' => $trxUser->firstname,
            'amount' => number_format($transaction->amount, 2),
            'reason' => $request->rejected_reason,
            'status' => 'withdrawal rejected',
            'balance' => number_format($wallet->balance, 2),
            'time' => date("h:iA, F jS, Y", strtotime("$time")),
        ];

        try {
            Mail::to($trxUser->email)->send(new \App\Mail\rejectedWithdraw($emailData));
        } catch (\Exception $e) {
            info($e);
        }



        // return response with message and data
        return response(['message' => 'Withdrawal Rejected successfully'], 200);
    }
    
      public function getReceiver(Request $request)
    {
        
        // user
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'username' => 'required',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all(), 'message' => $validator->errors()->all(),], 422);
        }

        $receiver = User::where("status", 1)->where("username", $request->username)->select("first_name", "last_name", "username", "photo", "email", "phone")->first();
        
        if($receiver){
            return response([
            'message' => 'user fetched successfully',
            'data' => $receiver,
        ], 200);
        }else{
            return response(['errors' => "user unavailable", 'message' => "User account doesn not exist on our platform or is unavailable",], 422);
        }
        

        // return response with form data and message
        return response([
            'message' => 'Wallet withdrawal request created successfully',
            'data' => $form,
        ], 200);
    }
    
     public function sendToUsername(Request $request)
     {


        // user
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'username' => 'required'
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $wallet = $user->wallet;

        // check if user has bank details
        $receiver = User::where("status", 1)->where("username", $request->username)->first();
        if(!$receiver) {
            return response(['errors' => "user unavailable", 'message' => "User account doesn not exist on our platform or is unavailable",], 422);
        }

        // check if the user has enough money to withdraw
        if ($wallet->balance < ($request->amount)) {
            return response(['errors' => ['You do not have enough money in your wallet to withdraw'], "message" => 'You do not have enough money in your wallet to withdraw'], 422);
        }

        // check if the balance is within the range of the minimum and maximum withdrawal amount
        if ($request->amount < 1000 || $request->amount > 5000000) {
            return response(['errors' => ['The amount you are trying to withdraw is not within the range of the minimum and maximum withdrawal amount'],  "message" => 'The amount you are trying to withdraw is not within the range of the minimum and maximum withdrawal amount'], 422);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }
        
        


        $walletTransaction = new WalletTransactions;
        $walletTransaction->user_id = $user->id;
        $walletTransaction->amount = $request->amount;
        $walletTransaction->bank_id = 0;
        $walletTransaction->status = 1;
        $walletTransaction->bank = 0;
        $walletTransaction->bank_name = "Faveremit";
        $walletTransaction->account_number = $receiver->id;
        $walletTransaction->account_name = $receiver->first_name . " " . $receiver->last_name;
        $walletTransaction->charge = 0;
        $walletTransaction->type = "transfer";
        
        // create unique transaction reference id
        $transaction_ref = uniqid();
        $walletTransaction->transaction_ref = $transaction_ref;
        $walletTransaction->save();


       
        
        
        // sort fund trx
        $fundTransaction = new FundTransaction();

            $fundTransaction->user_id = $receiver->id;
            $fundTransaction->amount = $request->amount;
            $fundTransaction->settlement = $request->amount;
            $fundTransaction->charge = 0;
            $fundTransaction->reference = $transaction_ref;
            $fundTransaction->profile_first_name = $receiver->last_name;
            $fundTransaction->profile_surname = $receiver->last_name;
            $fundTransaction->profile_phone_no =$receiver->phone;
            $fundTransaction->profile_email = $receiver->email;
            $fundTransaction->profile_blacklisted = "false";
            $fundTransaction->account_name = $receiver->first_name . " " . $receiver->last_name;
            $fundTransaction->account_no = $receiver->id;
            $fundTransaction->bank_name = "Faveremit";
            $fundTransaction->acccount_reference = $receiver->id;
            $fundTransaction->transaction_status = "Approved";
            $fundTransaction->status = 1;
            $fundTransaction->payer_account_name = $user->first_name . " " . $user->last_name;
            $fundTransaction->payer_account_no = $user->id;
            $fundTransaction->payer_bank_name = "Faveremit";
            $fundTransaction->save();
            
            
            // send and debit
            
             $wallet->balance = $wallet->balance - ($request->amount);
        $wallet->save();

        $rec_wallet = $receiver->wallet;
        $rec_wallet->balance = $wallet->balance + ($request->amount);
        $rec_wallet->save();
        
        
        $amount = number_format($request->amount, 2);


        try {

                FCMService::send(
                $user->fcm,
                [
                    'title' => 'Transfer Successfull',
                    'body' => "You hhave successfully transfered NGN" . $request->amount . " to " . $receiver->first_name,
                    ]
            );
            
            FCMService::send(
                $receiver->fcm,
                [
                    'title' => 'Funds Received',
                    'body' => "You just got an amount of " . $request->amount . " from " . $user->first_name,
                ]
            );

            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $walletTransaction->created_at->setTimezone($wa_time);

            //send a funds confirmation email to the user
            Mail::to($user->email)->send(new \App\Mail\WithdrawWallet($user, $amount, number_format($wallet->balance, 2), date("h:iA, F jS, Y", strtotime("$time"))));
        } catch (\Exception $e) {
            info($e);
        }

        // create form with the transaction reference id, amount and status
        $form = [
            'user_id' => $user->id,
            'amount' => $request->amount,
            'status' => 0,
            'transaction_ref' => $transaction_ref,
        ];

        // return response with form data and message
        return response([
            'message' => 'Wallet withdrawal request created successfully',
            'data' => $form,
        ], 200);
    }
    
     public function verifyBank(Request $request)
    {
        
        // user
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'account_no' => 'required',
            'bank_code' => 'required',
            'bank_name' => 'required',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all(), 'message' => $validator->errors()->all(),], 422);
        }

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/2.6/kyc/bank-account/verify', $request->all());

        $server_output = json_decode($response);


        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200){
            return response(['message' => "account fetched successfully", "data" => $data['details'] ], 200);
            
        }else {
            return response(['errors' => ['Bank not found']], 422);
        }
        

        // return response with form data and message
        return response([
            'message' => 'Wallet withdrawal request created successfully',
            'data' => $form,
        ], 200);
    }
    
    public function sendToBankRequest(Request $request)
    {
        
         // get transfer charge
        $charge = Config::where("name", "withdrawal_charge")->first();
        $charge_amount = floatval($charge->value);
        
        // user
        $user = auth('api')->user();
        
        // user
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'account_no' => 'required',
            'bank_code' => 'required',
            'bank_name' => 'required',
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all(), 'message' => $validator->errors()->first(),], 422);
        }
        

        $wallet = $user->wallet;

        // check if user has bank details
        $bankAccount = BankDetails::where('user_id', $user->id)->first();
        if (!$bankAccount) {
            return response(['message' => 'You have no bank details'], 400);
        }

        // check if the user has enough money to withdraw
        if ($wallet->balance < ($request->amount)) {
            return response(['message' => 'You do not have enough money in your wallet to withdraw'], 422);
        }

        // check if the balance is within the range of the minimum and maximum withdrawal amount
        if ($request->amount < 1000 || $request->amount > 5000000) {
            return response(['message' => 'The amount you are trying to transfer is not within the range of the minimum and maximum transfer amount'], 422);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }
        
        

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/2.6/kyc/bank-account/verify', $request->all());

        $server_output = json_decode($response);


        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200){
            
        $walletTransaction = new WalletTransactions;
        $walletTransaction->user_id = $user->id;
        $walletTransaction->amount = $request->amount;
        $walletTransaction->bank_id = 0;
        $walletTransaction->status = 0;
        $walletTransaction->bank = $request->bank_code;
        $walletTransaction->bank_name = $request->bank_name;
        $walletTransaction->account_number = $request->account_no;
        $walletTransaction->account_name = $data['details']['account_name'];
        $walletTransaction->charge = $charge_amount;
        $walletTransaction->type = "transfer";

        $wallet = $user->wallet;
        $wallet->balance = $wallet->balance - ($request->amount);
        $wallet->save();

        // create unique transaction reference id
        $transaction_ref = uniqid();
        $walletTransaction->transaction_ref = $transaction_ref;
        $walletTransaction->save();

        $amount = number_format($request->amount, 2);
        
        try {

            FCMService::sendToAdmins(
                [
                    'title' => 'New Withdrawal Request',
                    'body' => "There is a new transfer request of NGN" . $request->amount . " by a Faveremit user",
                ]
            );

            FCMService::send(
                $user->fcm,
                [
                    'title' => 'Transfer Successfull',
                    'body' => "You have successfully transfered NGN" . $request->amount . " to " . $request->bank_name,
                    ]
            );
            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $walletTransaction->created_at->setTimezone($wa_time);


        } catch (\Exception $e) {
            info($e);
        }
            
        }else {
            return response(['message' => 'Bank not found'], 422);
        }
        

        // return response with form data and message
        return response([
            'message' => 'Transfer request created successfully',
            'data' => $walletTransaction,
        ], 200);
    }
    
    public function verifyRedWithdrawals(){
        
    
        Log::info("Starting rebiller withdrawal updates");
       
       
      $pending_trxs =  WalletTransactions::where("trx_status", "Pending")->get()->sortBy('created_at');
      
      foreach($pending_trxs as $trx){
          $body = [
               "reference"=> $trx->transaction_id
               ];
           
           $request = "https://api.live.redbiller.com/1.0/payout/bank-transfer/status";
           
          $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post($request, $body);
          
         Log::info($response);

        $data = $response->json();
        
        $user = User::where("id", $trx->user_id)->first();
        $wallet = Wallet::where("user_id", $trx->user_id)->first();
        
         if ($data['status'] == true && $data['response'] == 200) {
             $status = strtolower($data['meta']['status']);
             
             $trx->update([
                 "status" => $status == "approved" ? 1 : ($status == "pending" ? 1 : ($status == "cancelled" ? 2 : 1)),
                 "trx_status" => $data['meta']['status']
                 ]);
                 
            if($status == "cancelled"){
                    {
                    $wallet->balance = $wallet->balance + $trx->amount;
                    $wallet->save();
                    } 
                try {
                FCMService::send(
                    $user->fcm,
                    [
                        'title' => 'Transaction Failed',
                        'body' => "Your withdrawal with the reference " . $trx->transaction_ref . " has failed and your funds of NGN". $trx->amount ." has been reversed. Apologies for this inconvinience. Please check app for more details.",
                    ]
                );
                } //catch exception
                catch (Exception $e) {
                    Log::error('Message: ' . $e->getMessage());
                }
                
            }
         }
       }
     
       
    Log::info("Ending rebiller withdrawals updates");
    
    }
    
}
