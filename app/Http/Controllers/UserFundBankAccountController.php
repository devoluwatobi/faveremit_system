<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\UserFundBankAccount;
use App\Models\Verification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class UserFundBankAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth('api')->user();

        return response(['status' => true, 'message' => 'Fund Account Fetched successfully', 'fund_bank_details' => UserFundBankAccount::where("user_id", $user->id)->select('id', 'user_id', 'account_no', 'account_name', 'status', 'bank_name')->first(),], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {

        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'surname' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all(), 'fund_bank_details' => UserFundBankAccount::where("user_id", $user->id)->select('id', 'user_id', 'account_no', 'account_name', 'status', 'bank_name')->first(),], 422);
        }

        $user_fund_account = UserFundBankAccount::where("user_id", $user->id)->first();
        if ($user_fund_account) {
            return response(['status' => true, 'message' => 'Fund Account Fetched successfully', 'fund_bank_details' => UserFundBankAccount::where("user_id", $user->id)->select('id', 'user_id', 'account_no', 'account_name', 'status', 'bank_name')->first(),], 200);
        }

        $user_bvn = Verification::where("user_id", $user->id)->where("type", "bvn")->first();

        if (!$user_bvn || $user_bvn->status != "1" || !$user_bvn->value || !$user_bvn->dob || !$user_bvn->name) {
            return response(['status' => true, 'message' => 'Fund Account Creation Failed', 'fund_bank_details' => UserFundBankAccount::where("user_id", $user->id)->select('id', 'user_id', 'account_no', 'account_name', 'status', 'bank_name')->first(),], 422);
        }

        $separated_names = explode(' ', $user_bvn->name);
        $first_name = $separated_names[0];
        $last_name = $separated_names[1];

        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $body  = [
            "bank" => "VFD", //NowNow, Kuda, Paga, Wema, VFD, Providus
            "first_name" => $first_name,
            "surname" => $last_name,
            "phone_no" => str_replace('+234', '0', $user->phone),
            "email" => $user->email,
            "bvn" => $user_bvn->value,
            "date_of_birth" => Carbon::parse($user_bvn->dob)->format('Y-m-d'),
            "auto_settlement" => "true",
            // "gender" => "Female",
            "reference" => $transactionRef
        ];

        Log::info($body);


        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/collections/PSA/create', $body);

        $server_output = json_decode($response);

        $data = $response->json();

        Log::info($response);
        
        if ($data['status'] == false || $data['response'] == 400){
            
            $body  = [
            "bank" => "Paga", //NowNow, Kuda, Paga, Wema, VFD, Providus
            "first_name" => $first_name,
            "surname" => $last_name,
            "phone_no" => str_replace('+234', '0', $user->phone),
            "email" => $user->email,
            "bvn" => $user_bvn->value,
            "date_of_birth" => Carbon::parse($user_bvn->dob)->format('Y-m-d'),
            "auto_settlement" => "true",
            // "gender" => "Female",
            "reference" => $transactionRef
        ];

        Log::info($body);


        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/collections/PSA/create', $body);

        $server_output = json_decode($response);

        $data = $response->json();

        Log::info($response);
        }

        if ($data['status'] == true && $data['response'] == 200) {
            $account = new UserFundBankAccount();
            $account->user_id = $user->id;
            $account->account_name = $data['details']['sub_account']['account_name'];
            $account->account_no = $data['details']['sub_account']['account_no'];
            $account->bank_name = $data['details']['sub_account']['bank_name'];
            $account->auto_settlement = $data['details']['sub_account']['auto_settlement'];
            $account->reference = $data['details']['sub_account']['reference'];
            $account->status = 1;
            $account->save();

            return response(['status' => true, 'message' => 'Create Account Successfull', 'fund_bank_details' => UserFundBankAccount::where("user_id", $user->id)->select('id', 'user_id', 'account_no', 'account_name', 'status', 'bank_name')->first(),], 200);
        } else {
            return response(['status' => true, 'message' => 'Create Account Failed', 'fund_bank_details' => UserFundBankAccount::where("user_id", $user->id)->select('id', 'user_id', 'account_no', 'account_name', 'status', 'bank_name')->first(),], 422);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UserFundBankAccount  $userFundBankAccount
     * @return \Illuminate\Http\Response
     */
    public function show(UserFundBankAccount $userFundBankAccount)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UserFundBankAccount  $userFundBankAccount
     * @return \Illuminate\Http\Response
     */
    public function edit(UserFundBankAccount $userFundBankAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserFundBankAccount  $userFundBankAccount
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserFundBankAccount $userFundBankAccount)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserFundBankAccount  $userFundBankAccount
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserFundBankAccount $userFundBankAccount)
    {
        //
    }
}
