<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Verification;
use App\Services\SMSService;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $user = auth('api')->user();

        return response(['status' => true, 'message' => 'Verifications Fetched successfully', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 200);
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Verification  $verification
     * @return \Illuminate\Http\Response
     */
    public function show(Verification $verification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Verification  $verification
     * @return \Illuminate\Http\Response
     */
    public function edit(Verification $verification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Verification  $verification
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Verification $verification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Verification  $verification
     * @return \Illuminate\Http\Response
     */
    public function destroy(Verification $verification)
    {
        //
    }

    public function verifyBVNXX(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'account_no' => 'required',
            'bank_code' => 'required',
            'bank_name' => 'required',
            'bvn' => 'required',
        ]);


        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = auth('api')->user();

        $old_v = Verification::where("type", "bvn")->where("user_id", $user->id)->first();

        if ($old_v &&  $old_v->status == 1) {
            return response(['status' => true, 'message' => 'BVN verified and saved successfully', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 200);
        }


        // verify bank details first
        $bank_response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/2.6/kyc/bank-account/verify', $request->all());

        $bank_server_output = json_decode($bank_response);

        $bank_data = $bank_response->json();

        if ($bank_data['status'] == true && $bank_data['response'] == 200) {

            $bank_name = $bank_data['details']['account_name'];
            $bank_name = str_replace(",", "", $bank_name);
            $bank_name = explode(' ', $bank_name);
            $first_name = $bank_name[0];
            $last_name = $bank_name[1];

            // convert first name and last name to lowercase
            $first_name = strtolower($first_name);
            $last_name = strtolower($last_name);

            $userFirstName = $user->first_name;
            $userLastName = $user->last_name;

            if (!(strtolower($userFirstName) == $first_name || strtolower($userLastName) == $last_name || strtolower($userFirstName) == $last_name || strtolower($userLastName) == $first_name)) {
                return response(['status' => false, 'message' => 'BVN verification failed, Account name does not correlate with kdc trade account name', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
            }
        } else {
            return response(['status' => false, 'message' => 'BVN verification failed', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
        }
        // end verify bank details

        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        $body  = $request->all();
        $body["reference"] =  $transactionRef;

        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/kyc/bvn/verify.3.0', $body);

        $server_output = json_decode($response);

        $data = $response->json();

        Log::info($data);


        // return response(['status' => false, 'message' => 'BVN verification failed', 'verification_details' => Verification::where("user_id", $user->id)->get(), 'data' => $data], 422);




        if ($data['status'] == true && $data['response'] == 200) {

            $firstname = $data['details']['personal']['first_name'];
            $lastname = $data['details']['personal']['surname'];

            // convert first name and last name to lowercase
            $firstname = strtolower($firstname);
            $lastname = strtolower($lastname);

            $userFirstName = $user->first_name;
            $userLastName = $user->last_name;


            // check if firstname and lastname matches either firstname or lastname of user

            Log::info($data['details']['identification']['bvn'] == $request->bvn);


            if ((strtolower($userFirstName) == $firstname || strtolower($userLastName) == $lastname || strtolower($userFirstName) == $lastname || strtolower($userLastName) == $firstname) && $data['details']['identification']['bvn'] == $request->bvn) {

                // check if user has existing bank details
                $old_v = Verification::where("type", "bvn")->where("user_id", $user->id)->first(); {
                    if ($old_v) {
                        $old_v->update(
                            [
                                "verification_status" => $data['meta']['status'],
                                "name" => $data['details']['personal']['first_name'] . " " . $data['details']['personal']['surname'],
                                "reference" => $data['details']['reference'],
                                "status" => 1,
                                "value" => $data['details']['identification']['bvn'],
                                "dob" => $data['details']['personal']['date_of_birth'],
                            ]
                        );
                    } else {
                        $verification = new Verification();
                        $verification->user_id = $user->id;
                        $verification->type = "bvn";
                        $verification->verification_status = $data['meta']['status'];
                        $verification->name = $data['details']['personal']['first_name'] . " " . $data['details']['personal']['surname'];
                        $verification->reference = $data['details']['reference'];
                        $verification->value = $data['details']['identification']['bvn'];
                        $verification->status = 1;
                        $verification->dob = $data['details']['personal']['date_of_birth'];

                        $verification->save();
                    }
                }

                $user->update([
                    "first_name" => $data['details']['personal']['first_name'],
                    "last_name" => $data['details']['personal']['surname'],
                ]);

                return response(['status' => true, 'message' => 'BVN verified and saved successfully', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 200);
            } else {

                $old_v = Verification::where("type", "bvn")->where("user_id", $user->id)->first(); {
                    if ($old_v) {
                        $old_v->update(
                            [
                                "verification_status" => "Failed",
                                "name" =>  "nil",
                                "reference" => "nil",
                                "status" => 0,
                            ]
                        );
                    } else {
                        $verification = new Verification();
                        $verification->user_id = $user->id;
                        $verification->type = "bvn";
                        $verification->verification_status = "Failed";
                        $verification->name = "nil";
                        $verification->reference = "nil";
                        $verification->status = 0;

                        $verification->save();
                    }
                }


                return response(['status' => false, 'message' => 'BVN verification failed, Account name does not correlate with kdc trade account name', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
            }
        } else {
            $old_v = Verification::where("type", "bvn")->where("user_id", $user->id)->first(); {
                if ($old_v) {
                    $old_v->update(
                        [
                            "verification_status" => "Failed",
                            "name" =>  "nil",
                            "reference" => "nil",
                            "status" => 0,
                        ]
                    );
                } else {
                    $verification = new Verification();
                    $verification->user_id = $user->id;
                    $verification->type = "bvn";
                    $verification->verification_status = "Failed";
                    $verification->name = "nil";
                    $verification->reference = "nil";
                    $verification->status = 0;

                    $verification->save();
                }
            }

            return response(['status' => false, 'message' => 'BVN verification failed', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
        }
    }

    public function verifyBVN(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'account_no' => 'required',
            'bank_code' => 'required',
            'bank_name' => 'required',
            'bvn' => 'required',
        ]);


        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = auth('api')->user();

        // $old_v = Verification::where("type", "bvn")->where("user_id", $user->id)->first();
        $old_v = Verification::where("status", 1)->where("user_id", $user->id)->first();

        if ($old_v &&  $old_v->status == 1) {
            return response(['status' => true, 'message' => 'BVN verified and saved successfully', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 200);
        }

        $body = $request->all();

        $body['bvn'] = null;

        // verify bank details first
        $bank_response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/2.0/kyc/bank-account/verify', $body);



        $bank_server_output = json_decode($bank_response);

        $bank_data = $bank_response->json();

        Log::info($bank_data);

        if ($bank_data['status'] == true && $bank_data['response'] == 200) {

            $bank_name = $bank_data['details']['account_name'];
            $bank_name = str_replace(",", "", $bank_name);
            $bank_name = strtolower($bank_name);
            $bank_names = explode(' ', $bank_name);
            $first_name = $bank_names[0];
            $last_name = $bank_names[1];

            // convert first name and last name to lowercase
            $first_name = strtolower($first_name);
            $last_name = strtolower($last_name);

            $userFirstName = strtolower($user->first_name);
            $userLastName = strtolower($user->last_name);



            if (!(str_contains($bank_name, $userFirstName) || str_contains($bank_name, $userLastName))) {
                return response(['status' => false, 'message' => 'BVN verification failed, Account name does not correlate with Faveremit account name', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
            }
        } else {
            return response(['status' => false, 'message' => 'BVN verification failed', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
        }
        // end verify bank details

        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');


        // Dojah
        $response = Http::withHeaders(['Content-Type' => 'application/json', 'Authorization' => env("DOJAH_KEY"), 'AppId' => env("DOJAH_APP_ID")])->get(env("DOJAH_PRODUCTION") . '/api/v1/kyc/bvn/full?bvn=' . $request->bvn);

        $server_output = json_decode($response);

        $data = $response->json();

        Log::info($data);


        if (isset($data['entity']) && isset($data['entity']['bvn'])) {
            $firstname = $data['entity']['first_name'];
            $lastname = $data['entity']['last_name'];

            // convert first name and last name to lowercase
            $firstname = strtolower($firstname);
            $lastname = strtolower($lastname);

            $userFirstName = $user->first_name;
            $userLastName = $user->last_name;

            $name = $user->first_name . ' ' . $user->last_name;

            Log::info($data['entity']['bvn'] == $request->bvn);

            if ((str_contains(strtolower($name), $firstname) || str_contains(strtolower($name), $lastname)) && $data['entity']['bvn'] == $request->bvn) {

                // check if user has existing bank details
                $old_v = Verification::where("type", "bvn")->where("user_id", $user->id)->first(); {
                    if ($old_v) {
                        $old_v->update(
                            [
                                "verification_status" => "Approved",
                                "name" => $data['entity']['first_name'] . " " . $data['entity']['last_name'],
                                "reference" => $transactionRef,
                                "status" => 1,
                                "value" => $data['entity']['bvn'],
                                "dob" => $data['entity']['date_of_birth'],
                                "data" => $response
                            ]
                        );
                    } else {
                        $verification = new Verification();
                        $verification->user_id = $user->id;
                        $verification->type = "bvn";
                        $verification->verification_status = "Approved";
                        $verification->name = $data['entity']['first_name'] . " " . $data['entity']['last_name'];
                        $verification->reference = $transactionRef;
                        $verification->value = $data['entity']['bvn'];
                        $verification->status = 1;
                        $verification->dob = $data['entity']['date_of_birth'];
                        $verification->data = $response;

                        $verification->save();
                    }
                }

                $theuser =  User::where("id", $user->id)->first();
                $theuser->update([
                    "first_name" => $data['entity']['first_name'],
                    "last_name" => $data['entity']['last_name']
                ]);

                try {
                    FCMService::send(
                        $user->fcm,
                        [
                            'title' => 'BVN Verification Successful',
                            'body' => " Congratulations!, your bvn verification was successfull. You have unlocked new limits.",
                        ]
                    );
                    // Mail::to($user->email)->send(new \App\Mail\VerificationMail($user->first_name, "bvn"));
                } catch (Exception $e) {
                    Log::error('Message: ' . $e->getMessage());
                }

                return response(['status' => true, 'message' => 'BVN verified and saved successfully', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 200);
            } else {

                $old_v = Verification::where("type", "bvn")->where("user_id", $user->id)->first(); {
                    if ($old_v) {
                        $old_v->update(
                            [
                                "verification_status" => "Pending",
                                "name" => $data['entity']['first_name'] . " " . $data['entity']['last_name'],
                                "reference" => $transactionRef,
                                "status" => 0,
                                "value" => $data['entity']['bvn'],
                                "dob" => $data['entity']['date_of_birth'],
                                "data" => $response
                            ]
                        );
                    } else {
                        $verification = new Verification();
                        $verification->user_id = $user->id;
                        $verification->type = "bvn";
                        $verification->verification_status = "Pending";
                        $verification->name = $data['entity']['first_name'] . " " . $data['entity']['last_name'];
                        $verification->reference = $transactionRef;
                        $verification->value = $data['entity']['bvn'];
                        $verification->status = 0;
                        $verification->dob = $data['entity']['date_of_birth'];
                        $verification->data = $response;

                        $verification->save();
                    }
                }


                return response(['status' => false, 'message' => 'BVN verification failed, Account name does not correlate with Faveremit account name', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
            }
        } else {
            $old_v = Verification::where("type", "bvn")->where("user_id", $user->id)->first(); {
                if ($old_v) {
                    $old_v->update(
                        [
                            "verification_status" => "Failed",
                            "name" =>  "nil",
                            "reference" => "nil",
                            "data" => $response
                        ]
                    );
                } else {
                    $verification = new Verification();
                    $verification = new Verification();
                    $verification->user_id = $user->id;
                    $verification->type = "bvn";
                    $verification->verification_status = "Failed";
                    $verification->name = "nil";
                    $verification->reference = "nil";
                    $verification->status = 0;
                    $verification->data = $response;

                    $verification->save();
                }
            }

            return response(['status' => false, 'message' => 'BVN verification failed', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
        }
    }

    public function verifyNIN(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'surname' => 'required',
            'phone_no' => 'required',
            'nin' => 'required',
        ]);


        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = auth('api')->user();

        // $old_v = Verification::where("type", "nin")->where("user_id", $user->id)->first();
        $old_v = Verification::where("status", 1)->where("user_id", $user->id)->first();

        if ($old_v &&  $old_v->status == 1) {
            return response(['status' => true, 'message' => 'NIN verified and saved successfully', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 200);
        }

        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');


        // Dojah
        $response = Http::withHeaders(['Content-Type' => 'application/json', 'Authorization' => env("DOJAH_KEY"), 'AppId' => env("DOJAH_APP_ID")])->get(env("DOJAH_PRODUCTION") . '/api/v1/kyc/nin?nin=' . $request->nin);

        $server_output = json_decode($response);

        $data = $response->json();

        Log::info($data);


        if (isset($data['entity']) && isset($data['entity']['date_of_birth'])) {
            $firstname = $data['entity']['first_name'];
            $lastname = $data['entity']['last_name'];

            // convert first name and last name to lowercase
            $firstname = strtolower($firstname);
            $lastname = strtolower($lastname);

            $userFirstName = $user->first_name;
            $userLastName = $user->last_name;

            $name = $user->first_name . ' ' . $user->last_name;

            $name = strtolower($name);

            //  Log::info($data['entity']['nin'] == $request->nin);

            if (str_contains($name, $firstname) || str_contains($bank_name, $lastname)) {

                // check if user has existing bank details
                $old_v = Verification::where("type", "nin")->where("user_id", $user->id)->first(); {
                    if ($old_v) {
                        $old_v->update(
                            [
                                "verification_status" => "Approved",
                                "name" => $data['entity']['first_name'] . " " . $data['entity']['last_name'],
                                "reference" => $transactionRef,
                                "status" => 1,
                                "value" => $request->nin,
                                "data" => $response,
                                "dob" => $data['entity']['date_of_birth'],
                            ]
                        );
                    } else {
                        $verification = new Verification();
                        $verification->user_id = $user->id;
                        $verification->type = "nin";
                        $verification->verification_status = "Approved";
                        $verification->name = $data['entity']['first_name'] . " " . $data['entity']['last_name'];
                        $verification->reference = $transactionRef;
                        $verification->value = $request->nin;
                        $verification->status = 1;
                        $verification->data = $response;
                        $verification->dob = $data['entity']['date_of_birth'];

                        $verification->save();
                    }
                }

                $theuser =  User::where("id", $user->id)->first;
                $theuser->update([
                    "first_name" => $data['entity']['first_name'],
                    "last_name" => $data['entity']['last_name'],
                ]);

                try {
                    // Mail::to($user->email)->send(new \App\Mail\VerificationMail($user->first_name, "nin"));
                } catch (Exception $e) {
                    Log::error('Message: ' . $e->getMessage());
                }

                try {
                    FCMService::send(
                        $user->fcm,
                        [
                            'title' => 'NIN Verification Complete',
                            'body' => "You just successfully verified your NIN on the Faveremit",
                        ]
                    );
                } catch (Exception $e) {
                    Log::error('Message: ' . $e->getMessage());
                }

                return response(['status' => true, 'message' => 'NIN verified and saved successfully', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 200);
            } else {

                $old_v = Verification::where("type", "nin")->where("user_id", $user->id)->first(); {
                    if ($old_v) {
                        $old_v->update(
                            [
                                "verification_status" => "Pending",
                                "name" => $data['entity']['first_name'] . " " . $data['entity']['last_name'],
                                "reference" => $transactionRef,
                                "status" => 0,
                                "value" => $request->nin,
                                "data" => $response,
                                "dob" => $data['entity']['date_of_birth'],
                            ]
                        );
                    } else {
                        $verification = new Verification();
                        $verification->user_id = $user->id;
                        $verification->type = "nin";
                        $verification->verification_status = "Pending";
                        $verification->name = $data['entity']['first_name'] . " " . $data['entity']['last_name'];
                        $verification->reference = $transactionRef;
                        $verification->value = $request->nin;
                        $verification->status = 0;
                        $verification->data = $response;
                        $verification->dob = $data['entity']['date_of_birth'];

                        $verification->save();
                    }
                }


                return response(['status' => false, 'message' => 'NIN verification failed, Account name does not correlate with Faveremit account name', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
            }
        } else {
            $old_v = Verification::where("type", "nin")->where("user_id", $user->id)->first(); {
                if ($old_v) {
                    $old_v->update(
                        [
                            "verification_status" => "Failed",
                            "name" =>  "nil",
                            "reference" => "nil",
                            "data" => $response
                        ]
                    );
                } else {
                    $verification = new Verification();
                    $verification->user_id = $user->id;
                    $verification->type = "nin";
                    $verification->verification_status = "Failed";
                    $verification->name = "nil";
                    $verification->reference = "nil";
                    $verification->status = 0;
                    $verification->data = $response;

                    $verification->save();
                }
            }

            return response(['status' => false, 'message' => 'NIN verification failed', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
        }
    }

    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:15',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = User::where("phone", $request->phone)->first();

        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $body  = [
            "phone_no" => str_replace('+234', '0', $request->phone),
            "type" => "NUMERIC",
            "case" => "DEFAULT",
            "length" => 6,
            "minutes_active" => 10,
            "message" => "Hello " . $user->first_name . ", Your Faveremit confirmation code is  #OTP. It expires in 30min.",
            "reference" => $transactionRef
        ];


        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/otp/create', $body);

        $server_output = json_decode($response);

        $data = $response->json();

        Log::info($response);


        if ($data['status'] == true && $data['response'] == 200) {
            $response = [
                'message' => 'otp sent'
            ];
            return response($response, 200);
        } else {
            $response = [
                'message' => 'otp failed'
            ];
            return response($response, 422);
        }
    }

    public function verifyNumber(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'otp' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = auth('api')->user();

        $body  = $request->all();

        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');


        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/otp/verify', $body);

        $server_output = json_decode($response);


        $data = $response->json();

        Log::info($response);

        if ($data['status'] == true && $data['response'] == 200) {


            $form = ['phone_verified_at' =>  date("Y-m-d H:i:s", strtotime('now'))];
            User::where('id', $user->id)->update($form);

            $old_v = Verification::where("type", "number")->where("user_id", $user->id)->first(); {
                if ($old_v) {
                    $old_v->update(
                        [
                            "verification_status" => $data['status'],
                            "name" =>  $user->first_name . ' ' . $user->flast_name,
                            "reference" => $data['details']['reference'],
                            "status" => 1,
                        ]
                    );
                } else {
                    $verification = new Verification();
                    $verification->user_id = $user->id;
                    $verification->type = "number";
                    $verification->verification_status = $data['status'];
                    $verification->name = $user->first_name . ' ' . $user->flast_name;
                    $verification->reference = $data['details']['reference'];
                    $verification->status = 1;

                    $verification->save();
                }
            }


            return response(['status' => true, 'message' => 'Number verified and saved successfully', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 200);
        } else {
            $old_v = Verification::where("type", "number")->where("user_id", $user->id)->first(); {
                if ($old_v) {
                    $old_v->update(
                        [
                            "verification_status" => "Failed",
                            "name" =>  "nil",
                            "reference" => "nil",
                            "status" => 0,
                        ]
                    );
                } else {
                    // $verification = new Verification();
                    // $verification->user_id = $user->id;
                    // $verification->type = "number";
                    // $verification->verification_status = "Failed";
                    // $verification->name = "nil";
                    // $verification->reference = "nil";
                    // $verification->status = 0;

                    // $verification->save();
                }
            }
            return response(['status' => false, 'message' => 'Number verification failed', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
        }
    }
}
// export PATH=/opt/homebrew/bin:$PATH:/Applications/XAMPP/bin:$PATH:/Developer/flutter/bin
