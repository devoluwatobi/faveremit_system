<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\BettingTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BettingTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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

    public function getBettingPlatforms()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.live.redbiller.com/1.5/bills/betting/providers/list",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Private-Key:" . env("REDBILLER_PRIV_KEY")
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        
        Log::info("redbiller => response " . $response);
        
        Log::info($response);
        
        

        return response($response, 200);
    }


    public function verifyBettingAccount(Request $request)
    {

        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'product' => 'required',
            'customer_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        // make http request with Authorization header
        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.5/bills/betting/account/verify', $request->all());

        $server_output = json_decode($response);

        $data = $response->json();

        return response($data, $server_output->response);
    }

    public function fundBettingAccount(Request $request)
    {

        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'product' => 'required',
            'customer_id' => 'required',
            'amount' => 'required',
            // 'reference' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        if ($user->status != 1 || $user->status != 1) {
            $response = ["message" => 'User account deactivated'];
            return response($response, 423);
        }

        $wallet = $user->wallet;

        // check if the user has enough money to withdraw
        if ($wallet->balance < $request->amount) {
            return response(['errors' => ['You do not have enough money in your wallet to withdraw']], 422);
        }

        $useRef = Carbon::now()->addHours(1);
        $transactionRef = $useRef->format('Ymdhis');

        $body = $request->all();
        $body["reference"] = $transactionRef;


        // make http request with Authorization header
        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.5/bills/betting/account/payment/create', $body);

        $server_output = json_decode($response);

        $data = $response->json();
        if ($server_output->response == 200) {
            $data = [
                'user_id' => $user->id,
                'amount' => $server_output->details->amount,
                'reference' => $server_output->details->reference,
                'product' => $server_output->details->product,
                'customer_id' => $server_output->details->customer_id,
                'first_name' => $server_output->details->profile->first_name,
                'surname' => $server_output->details->profile->surname,
                'username' => $server_output->details->profile->username,
                'date' => $server_output->details->date,
                'charge' => $server_output->details->charge,
                'bet_status' => $server_output->meta->status,
                'status' => 1,
            ];
            $trx =   BettingTransaction::create($data);

            return response($trx, 200);
        }

        return response($data, $server_output->response);
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
     * @param  \App\Models\BettingTransaction  $bettingTransaction
     * @return \Illuminate\Http\Response
     */
    public function show(BettingTransaction $bettingTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BettingTransaction  $bettingTransaction
     * @return \Illuminate\Http\Response
     */
    public function edit(BettingTransaction $bettingTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BettingTransaction  $bettingTransaction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BettingTransaction $bettingTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BettingTransaction  $bettingTransaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(BettingTransaction $bettingTransaction)
    {
        //
    }
}
