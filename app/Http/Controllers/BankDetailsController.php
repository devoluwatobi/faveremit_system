<?php

namespace App\Http\Controllers;

use App\Models\BankDetail;
use App\Models\BankDetails;
use Facade\FlareClient\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreBankDetailRequest;
use App\Http\Requests\UpdateBankDetailRequest;

class BankDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth('api')->user();
        $banks = $user->banks()->select('id', 'user_id', 'account_number', 'account_name', 'bank', 'bank_name')->first();

        // if user has bank details
        if ($banks) {
            $data = [
                'status' => 'success',
                'data' => $banks,
            ];
        } else {
            $data = [
                'status' => 'error',
                'message' => 'No bank details found',
            ];
        }


        return response($data, 200);
    }

     public function getRedBillerBanks()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.live.redbiller.com/1.0/payout/bank-transfer/banks/list",
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

        return response($response, 200);
    }

    public function verify(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'account_number' => 'required',
            'bank_code' => 'required',
            'bank_name' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = auth('api')->user();

        // make http request with Authorization header
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . env('PAYSTACK_KEY')])->get(env('PAYSTACK_API') . 'bank/resolve?account_number=' . $request->account_number . '&bank_code=' . $request->bank_code);

        $data = $response->json();

        if ($data['status'] == true) {

            $bankname = $data['data']['account_name'];

            // return response([
            //     'status' => 'success',
            //     'data' => [
            //         'account_name' => $bankname,
            //     ],
            // ], 200);
            //get first name and last name from bank name
            $bankname = explode(' ', $bankname);
            $firstname = $bankname[0];
            $lastname = $bankname[1];

            // convert first name and last name to lowercase
            $firstname = strtolower($firstname);
            $lastname = strtolower($lastname);

            // $bankname = explode(' ', $user->name);
            // $userFirstName = $bankname[0];
            // $userLastName = $bankname[1];
            $userFirstName = $user->first_name;
            $userLastName = $user->last_name;


            // check if firstname and lastname matches either firstname or lastname of user
            if (strtolower($userFirstName) == $firstname || strtolower($userLastName) == $lastname || strtolower($userFirstName) == $lastname || strtolower($userLastName) == $firstname) {

                $customer =  Http::withHeaders(['Authorization' => 'Bearer ' . env('PAYSTACK_KEY')])->post(env('PAYSTACK_API') . 'customer', [
                    'email' => $user->email,
                    'first_name' => $firstname,
                    'last_name' => $lastname,
                    'phone' => $user->phone,
                ]);

                $cusdata = $customer->json();

                // check if user has existing bank details
                $bank = $user->banks()->select('id', 'user_id', 'account_number', 'account_name', 'bank')->first();

                // if user has bank details
                if ($bank) {
                    // update bank details
                    $bank->update([
                        'account_number' => $request->account_number,
                        'account_name' => $firstname . ' ' . $lastname,
                        'bank' => $request->bank_code,
                        'bank_name' => $request->bank_name,
                        'paystack_id' => $cusdata['data']['id'],
                    ]);
                } else {
                    $bank = new BankDetails();
                    $bank->user_id = $user->id;
                    $bank->account_number = $request->account_number;
                    $bank->account_name = $firstname . ' ' . $lastname;
                    $bank->bank = $request->bank_code;
                    $bank->bank_name = $request->bank_name;
                    $bank->paystack_id = $cusdata['data']['id'];
                    $bank->save();
                }


                return response(['status' => true, 'message' => 'Bank account verified and saved successfully'], 200);
            } else {
                return response(['status' => false, 'message' => 'Bank account verification failed'], 422);
            }
        } else {
            return response(['errors' => ['Bank not found']], 422);
        }
    }


    public function getBanks()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.flutterwave.com/v3/banks/NG",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer FLWSECK_TEST-5e0347bfb74c3318889f5b0238a059c3-X"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return response($response, 200);
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
    
    
    public function verifyCreateRedBillerAccount(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'account_no' => 'required',
            'bank_code' => 'required',
            'bank_name' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = auth('api')->user();



        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/2.6/kyc/bank-account/verify', $request->all());

        $server_output = json_decode($response);


        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200) {

            $bankname = $data['details']['account_name'];

            $bankname = str_replace(",", "", $bankname);
            $bankname = explode(' ', $bankname);
            $firstname = $bankname[0];
            $lastname = $bankname[1];

            // convert first name and last name to lowercase
            $firstname = strtolower($firstname);
            $lastname = strtolower($lastname);

            $userFirstName = $user->first_name;
            $userLastName = $user->last_name;


            // check if firstname and lastname matches either firstname or lastname of user
            if (strtolower($userFirstName) == $firstname || strtolower($userLastName) == $lastname || strtolower($userFirstName) == $lastname || strtolower($userLastName) == $firstname) {

                // check if user has existing bank details
                $bank = $user->banks()->select('id', 'user_id', 'account_number', 'account_name', 'bank')->first(); {
                    $bank = new BankDetails();
                    $bank->user_id = $user->id;
                    $bank->account_number = $request->account_no;
                    $bank->account_name = $data['details']['account_name'];
                    $bank->bank = $request->bank_code;
                    $bank->bank_name = $request->bank_name;
                    $bank->paystack_id = 0;
                    $bank->save();
                }


                return response(['status' => true, 'message' => 'Bank account verified and saved successfully'], 200);
            } else {
                return response(['status' => false, 'message' => 'Bank account verification failed'], 422);
            }
        } else {
            return response(['errors' => ['Bank not found']], 422);
        }
    }
    
    public function verifyUpdateRedBillerAccount(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'account_no' => 'required',
            'bank_code' => 'required',
            'bank_name' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = auth('api')->user();



        // make http request with Authorization header
        // $response = Http::withHeaders(['Authorization' => 'Bearer ' . env('PAYSTACK_KEY')])->get(env('PAYSTACK_API') . 'bank/resolve?account_number=' . $request->account_number . '&bank_code=' . $request->bank_code);

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/2.6/kyc/bank-account/verify', $request->all());

        $server_output = json_decode($response);


        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200) {

            $bankname = $data['details']['account_name'];
            
            $bank_name = $data['details']['account_name'];
            

            // return response([
            //     'status' => 'success',
            //     'data' => [
            //         'account_name' => $bankname,
            //     ],
            // ], 200);
            //get first name and last name from bank name
            $bankname = str_replace(",", "", $bankname);
            $bankname = explode(' ', $bankname);
            $firstname = strtolower($bankname[0]);
            $lastname = strtolower($bankname[1]);
            

            // convert first name and last name to lowercase
            $firstname = strtolower($firstname);
            $lastname = strtolower($lastname);
            
            

            //  $user_full_name = str_replace(",", "", $user->name);
            //  $user_full_name = explode(' ', $user_full_name);
            $userFirstName = $user->first_name;
            $userLastName = $user->last_name;
            
            
            


            // check if firstname and lastname matches either firstname or lastname of user
            if ((str_contains(strtolower($bank_name), strtolower($user->first_name))) || (str_contains(strtolower($bank_name), strtolower($user->last_name)))) {

                // check if user has existing bank details
                $bank = BankDetails::where("user_id", $user->id)->first();

                // if user has bank details
                if ($bank) {
                    // update bank details
                    $bank->update([
                        'account_number' => $request->account_no,
                        'account_name' => $data['details']['account_name'],
                        'bank' => $request->bank_code,
                        'bank_name' => $request->bank_name,
                        'paystack_id' => 0,
                    ]);
                } else {
                    $bank = new BankDetails();
                    $bank->user_id = $user->id;
                    $bank->account_number = $request->account_no;
                    $bank->account_name = $data['details']['account_name'];
                    $bank->bank = $request->bank_code;
                    $bank->bank_name = $request->bank_name;
                    $bank->paystack_id = 0;
                    $bank->save();
                }

                 $bank =BankDetails::where('user_id', $user->id)->select('id', 'user_id', 'account_number', 'account_name', 'bank', 'bank_name')->first();

                return response(['status' => true,  'message' => 'Bank account verified and saved successfully', 'data' => $bank,], 200);
            } else {
                return response(['status' => false, 'message' => 'Bank account verification failed'], 422);
            }
        } else {
            return response(['errors' => ['Bank not found']], 422);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreBankDetailRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'bank' => 'required',
            'account_number' => 'required',
            'account_name' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        BankDetails::create([
            'user_id' => $user->id,
            'bank' => $request->bank,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
        ]);

        $response = [
            "message" => "Bank account saved",
        ];
        return response($response, 200);
    }

    public function updateBank(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'bank' => 'required',
            'account_number' => 'required',
            'account_name' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $bank = BankDetails::where('id', $request->id)->first();
        BankDetails::find($bank->id)->update([
            'bank' => $request->bank,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
        ]);

        $response = [
            "message" => "Bank account updated",
        ];
        return response($response, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BankDetail  $bankDetail
     * @return \Illuminate\Http\Response
     */
    public function show(BankDetails $bankDetail)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BankDetail  $bankDetail
     * @return \Illuminate\Http\Response
     */
    public function edit(BankDetails $bankDetail)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateBankDetailRequest  $request
     * @param  \App\Models\BankDetail  $bankDetail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BankDetail  $bankDetail
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
