<?php

namespace App\Http\Controllers;

use Exception;
use DateTimeZone;
use Carbon\Carbon;
use App\Models\Utility;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Services\FCMService;
use Illuminate\Http\Request;
use App\Models\BillTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreUtilityRequest;
use App\Http\Requests\UpdateUtilityRequest;


class UtilityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.flutterwave.com/v3/bill-categories?airtime=1",
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

    public function getNetworks()
    {
        // create an object array
        $mtn = [
            'Network' => 'MTN',
            'service_id' => Utility::NETWORK_MTN,
            'data_service_id' => Utility::NETWORK_MTN . '-data',
            'image' => env('APP_URL') . '/images/utility/mtn.png',
            'red_product' => "MTN"
        ];
        $mobile9 = [
            'Network' => '9Mobile',
            'service_id' => Utility::NETWORK_9MOBILE,
            'data_service_id' => Utility::NETWORK_9MOBILE . '-data',
            'image' => env('APP_URL') . '/images/utility/9-mobile.png',
            'red_product' => "9mobile"
        ];
        $airtel = [
            'Network' => 'Airtel',
            'service_id' => Utility::NETWORK_AIRTEL,
            'data_service_id' => Utility::NETWORK_AIRTEL . '-data',
            'image' => env('APP_URL') . '/images/utility/airtel.png',
            'red_product' => "Airtel"
        ];
        $glo = [
            'Network' => 'Glo',
            'service_id' => Utility::NETWORK_GLO,
            'data_service_id' => Utility::NETWORK_GLO . '-data',
            'image' => env('APP_URL') . '/images/utility/glo.png',
            'red_product' => "Glo"
        ];

        // create an array
        $networks = [
            $mtn,
            $mobile9,
            $airtel,
            $glo,
        ];


        return response($networks, 200);
    }
    
    public function getInternetServices()
    {
        // create an object array
        $smile = [
            'Network' => 'Smile',
            'service_id' => 'Smile',
            'data_service_id' => 'Smile',
            'image' => env('APP_URL') . '/images/utility/smile.png',
            'red_product' => 'Smile'
        ];

        $spectranet = [
            'Network' => 'Spectranet',
            'service_id' => 'Spectranet',
            'data_service_id' => 'Spectranet',
            'image' => env('APP_URL') . '/images/utility/spectranet.png',
            'red_product' => 'Spectranet'
        ];


        // create an array
        $networks = [
            $smile,
            $spectranet,
        ];


        return response($networks, 200);
    }
    
    public function getRedInternetList($product)
    {

        // make sure product code is not empty
        if (!$product) {
            return response(['error' => 'Product is required'], 422);
        }

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/bills/internet/plans/list', ["product" => $product]);

        // convert response to json
        $response = $response->json();

        return response($response, 200);
    }
    
    public function verifyRedInternetDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_no' => 'required',
            'product' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.1/bills/internet/device/verify', $request->all());


        Log::info($response);

        $data = $response->json();



            return response($response->json(), $data['response']);
    }
    
    public function buyRedInternet(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'product' => 'required',
            'code' => 'required',
            'device_no' => 'required',
            'customer_name' => 'required',
            'variation_name' => 'required',
            'amount' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = auth('api')->user();
        $wallet = $user->wallet;
        $wallet = $user->wallet;


        if ($request->amount > $wallet->balance) {
            return response(['error' => 'Insufficient funds in Naira wallet'], 422);
        }


        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }

        // generate unique ref from date time in gmt +1
        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $body = [
            'product' => $request->product,
            'code' => $request->code,
            'device_no' => $request->device_no,
            'customer_name' => $request->customer_name,
            'reference' => $transactionRef,
        ];

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/bills/data/plans/purchase/create', $body);

        Log::info($response);

        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200) {
             $status = strtolower($data['meta']['status']);
            // update wallet balance
           if($status != "cancelled"){
                $wallet->balance = $wallet->balance - $request->amount;
                $wallet->save();
            }

            $ref =    time() . '-' . $user->id;
            switch ($request->product) {
                case "Smile":
                    $icon = '/images/utility/smile.png';
                    break;
                case "Spectranet":
                    $icon = '/images/utility/spectranet.png';
                    break;
                default:
                    $icon =  'uploads/images/utility/1653393129.png';
                    break;
            }
            if (str_contains($request->service_id, 'etisalat')) {
                $service = "9-Mobile";
            } else {
                $service = $request->product;
            }

            $billTransaction = new BillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->device_no;
            $billTransaction->type = 'Internet';
            $billTransaction->service_name = $request->product;
            $billTransaction->service_icon = $icon;
            $billTransaction->utility_id = 1;
            $billTransaction->transaction_ref = $transactionRef;
            $billTransaction->status = $status == "approved" ? 1 : ($status == "pending" ? 0 : ($status == "cancelled" ? 2 : 1));
            $billTransaction->trx_status = $data['meta']['status']; 
            $billTransaction->package = $request->variation_name;

            $billTransaction->save();


            // make amount readable
            $amount = number_format($request->amount, 2);

            try {
                FCMService::send(
                    $user->fcm,
                    [
                        'title' => 'Data Subscription Successfull',
                        'body' => "Your Data Subscription payment was successful. Pleae check app for more details.",
                    ]
                );
            } //catch exception
            catch (Exception $e) {
                Log::error('Message: ' . $e->getMessage());
            }

            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $billTransaction->created_at->setTimezone($wa_time);
            // Mail::to($user->email)->send(new \App\Mail\buyDataBundle($user, $amount, $request->number));


            try {
                Mail::to($user->email)->send(new \App\Mail\buyDataBundle($user, $amount, $request->device_no, $billTransaction->package, $ref, date("h:iA, F jS, Y", strtotime("$time"))));
            } catch (\Exception $e) {
                info($e);
            }
            return response(['success' => 'Data purchased successfully'], 200);
        } else {
            return response(['error' => 'Transaction failed'], 422);
        }
    }

    public function getTvList()
    {
        // create an object array
        $dstv = [
            'cable' => 'DSTV',
            'service_id' => Utility::CABLE_DSTV,
            'image' => env('APP_URL') . '/images/utility/dstv.png',
            'red_product' => 'DStv'
        ];
        $gotv = [
            'cable' => 'GOTV',
            'service_id' => Utility::CABLE_GOTV,
            'image' => env('APP_URL') . '/images/utility/gotv.png',
            'red_product' => 'GOtv'
        ];
        $startimes = [
            'cable' => 'STARTIMES',
            'service_id' => Utility::CABLE_STARTIMES,
            'image' => env('APP_URL') . '/images/utility/startimes.png',
            'red_product' => 'StarTimes'
        ];
        // $showmax = [
        //     'cable' => 'SHOWMAX',
        //     'service_id' => Utility::CABLE_SHOWMAX,
        //     'image' => env('APP_URL') . '/images/utility/showmax.png',
        // ];

        // create an array
        $tv = [
            $dstv,
            $gotv,
            $startimes,
            // $showmax,
        ];




        return response($tv, 200);
    }

    public function getElectricList()
    {
        // create an object array
        $ikeja = [
            'location' => 'lagos',
            'service_id' => Utility::POWER_IKEJA,
            'image' => env('APP_URL') . '/images/utility/ikedc.png',
            'red_product' => 'Ikeja'
        ];
        $eko = [
            'location' => 'lagos',
            'service_id' => Utility::POWER_EKO,
            'image' => env('APP_URL') . '/images/utility/ekedc.png',
            'red_product' => 'Eko'
        ];
        $kano = [
            'location' => 'kano',
            'service_id' => Utility::POWER_KANO,
            'image' => env('APP_URL') . '/images/utility/kedco.png',
            'red_product' => 'Kano'
        ];
        $ph = [
            'location' => 'portharcourt',
            'service_id' => Utility::POWER_PH,
            'image' => env('APP_URL') . '/images/utility/phed.png',
            'red_product' => 'Porthacourt'
        ];
        $jos = [
            'location' => 'jos',
            'service_id' => Utility::POWER_JOS,
            'image' => env('APP_URL') . '/images/utility/jed.png',
            'red_product' => 'Jos'
        ];
        $abuja = [
            'location' => 'abuja',
            'service_id' => Utility::POWER_ABUJA,
            'image' => env('APP_URL') . '/images/utility/aedc.png',
            'red_product' => 'Abuja'
        ];
        $kaduna = [
            'location' => 'kaduna',
            'service_id' => Utility::POWER_KADUNA,
            'image' => env('APP_URL') . '/images/utility/kaedco.png',
            'red_product' => 'Kaduna'
        ];
        $ibadan = [
            'location' => 'ibadan',
            'service_id' => Utility::POWER_IBADAN,
            'image' => env('APP_URL') . '/images/utility/ibedc.png',
            'red_product' => 'Ibadan'
        ];




        // create an array
        $electric = [
            $ikeja,
            $eko,
            $kano,
            $ph,
            $jos,
            $abuja,
            $kaduna,
            $ibadan,
        ];


        return response($electric, 200);
    }

    public function getElectricTypes()
    {
        // create an object array
        $types = [
            Utility::POWER_PREPAID,
            Utility::POWER_POSTPAID,
        ];

        return response($types, 200);
    }

    public function verifyMeter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meter_number' => 'required',
            'service_id' => 'required',
            'meter_type' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $response = Http::withBasicAuth(env('VTPASS_EMAIL'), env('VTPASS_PASSWORD'))->post(env('VTPASS_API') . 'merchant-verify', [
            'billersCode' => $request->meter_number,
            'serviceID' => $request->service_id,
            'type' => $request->meter_type,
        ]);

        // convert response to json
        $response = $response->json();

        return response($response, 200);
    }

    public function verifyRedMeter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meter_no' => 'required',
            'product' => 'required',
            'meter_type' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.1/bills/disco/meter/verify', $request->all());


        Log::info($response);

        $data = $response->json();



            return response($response->json(), $data['response']);
    }
    
    public function verifyRedDecoder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'smart_card_no' => 'required',
            'product' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/bills/cable/decoder/verify', $request->all());


        Log::info($response);

        $data = $response->json();



            return response($response->json(), $data['response']);
    }

    public function buyElectricity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meter_number' => 'required',
            'service_id' => 'required',
            'meter_type' => 'required',
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $ref = Carbon::now()->addHours(1)->format('YmdHis');

        $user = auth('api')->user();
        $wallet = $user->wallet;

        // check if user has enough money
        if ($wallet->balance < $request->amount) {
            return response()->json(['error' => 'Insufficient funds'], 401);
        }

        // check if the amount is greater than the minimum amount of 500
        if ($request->amount < 500) {
            return response()->json(['error' => 'Minimum amount is 500'], 401);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }

        $response = Http::withBasicAuth(env('VTPASS_EMAIL'), env('VTPASS_PASSWORD'))->post(env('VTPASS_API') . 'pay', [
            'billersCode' => $request->meter_number,
            'serviceID' => $request->service_id,
            'request_id' => $ref,
            'variation_code' => $request->meter_type,
            'amount' => $request->amount,
            'phone' => $user->phone,
        ]);

        $server_output = json_decode($response);

        if ($server_output->content->transactions->status == 'delivered') {
            // update wallet balance
            $wallet->balance = $wallet->balance - $request->amount;
            $wallet->save();

            $ref =    time() . '-' . $user->id;
            switch ($request->service_id) {
                case Utility::POWER_IKEJA:
                    $icon = '/images/utility/ikedc.png';
                    break;
                case Utility::POWER_JOS:
                    $icon = '/images/utility/jed.png';
                    break;
                case Utility::POWER_ABUJA:
                    $icon = '/images/utility/aedc.png';
                    break;
                case Utility::POWER_EKO:
                    $icon = '/images/utility/ekedc.png';
                    break;
                case Utility::POWER_IBADAN:
                    $icon = '/images/utility/ibedc.png';
                    break;
                case Utility::POWER_KADUNA:
                    $icon = '/images/utility/kaedco.png';
                    break;
                case Utility::POWER_KANO:
                    $icon = '/images/utility/kedco.png';
                    break;
                case Utility::POWER_PH:
                    $icon = '/images/utility/phed.png';
                    break;
                default:
                    $icon =  '/uploads/images/services//1653394162.png';
                    break;
            }
            // $transactForm = [
            //     'user_id' => $user->id,
            //     'service_id' => 3,
            //     'ngn_amount' => $request->amount,
            //     'transaction_ref' => $ref,
            //     'utility_id' => 4,
            //     'utility_service' => $request->service_id,
            //     'icon' => $icon,
            //     'title' => 'Power (' . ucwords($request->service_id, "-") . ')',
            //     'status' => 1,
            //     'package' => ucwords($request->service_id, "-")
            // ];

            // $transaction =  Transaction::create($transactForm);

            $billTransaction = new BillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->meter_number;
            $billTransaction->type = 'Power (' . ucwords($request->service_id, "-") . ')';
            $billTransaction->service_name = $request->service_id;
            $billTransaction->transaction_ref = $ref;
            $billTransaction->service_icon = $icon;
            $billTransaction->status = 1;
            $billTransaction->utility_id = 4;
            $billTransaction->save();

            // make amount readable
            $amount = number_format($request->amount, 2);
            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $billTransaction->created_at->setTimezone($wa_time);

            try {
                FCMService::send(
                    $user->fcm,
                    [
                        'title' => 'Electricity Bill Payment Successfull',
                        'body' => "Your Electricity bill payment with the reference " . $transaction->transaction_ref . " was successful. Pleae check app for more details.",
                    ]
                );
            } //catch exception
            catch (Exception $e) {
                Log::error('Message: ' . $e->getMessage());
            }

            try {
                Mail::to($user->email)->send(new \App\Mail\buyPower($user, $amount, $request->meter_number, ucwords($request->service_id, '-'), $ref, date("h:iA, F jS, Y", strtotime("$time"))));
            } catch (\Exception $e) {
                info($e);
            }

            return response(['success' => 'Power purchased successfully'], 200);
        } else {
            return response(['error' => 'Transaction failed'], 422);
        }
    }

    public function buyRedElectricity(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'meter_no' => 'required',
            'meter_type' => 'required',
            'phone_no' => 'required',
            'amount' => 'required',
            'product' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = auth('api')->user();
        $wallet = $user->wallet;


        if ($request->amount > $wallet->balance) {
            return response(['error' => 'Insufficient funds in Naira wallet'], 422);
        }


        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }

        // generate unique ref from date time in gmt +1
        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $body = [
            "meter_no" => $request->meter_no,
            "customer_name" => $request->customer_name,
            "meter_type" => $request->meter_type,
            'product' => $request->product,
            'phone_no' => $request->phone_no,
            'amount' => $request->amount,
            'reference' => $transactionRef,
        ];

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.1/bills/disco/purchase/create', $body);

        Log::info($response);

        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200) {
            $status = strtolower($data['meta']['status']);
            $response_2 = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post("https://api.live.redbiller.com/1.0/bills/disco/purchase/status", [
                "reference"=> $transactionRef
                ]);

            Log::info($response_2);
    
            $data_2 = $response_2->json();
            
            if($data_2['status'] == true && $data_2['response'] == 200){
                $status = strtolower($data['meta']['status']);
                 $data = $response_2->json();
            }
            // update wallet balance
            if($status != "cancelled"){
                $wallet->balance = $wallet->balance - $request->amount;
                $wallet->save();
            }

            $ref =    time() . '-' . $user->id;
            
                 switch ($request->product) {
                case "Ikeja":
                    $icon = '/images/utility/ikedc.png';
                    break;
                case "Jos":
                    $icon = '/images/utility/jed.png';
                    break;
                case "Abuja":
                    $icon = '/images/utility/aedc.png';
                    break;
                case "Eko":
                    $icon = '/images/utility/ekedc.png';
                    break;
                case "ibadan":
                    $icon = '/images/utility/ibedc.png';
                    break;
                case "Kaduna":
                    $icon = '/images/utility/kaedco.png';
                    break;
                case "Kano":
                    $icon = '/images/utility/kedco.png';
                    break;
                case "Portharcourt":
                    $icon = '/images/utility/phed.png';
                    break;
                default:
                    $icon =  '/uploads/images/services//1653394162.png';
                    break;
            
            }

            $billTransaction = new BillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->meter_no;
            $billTransaction->type = 'Power (' . ucwords($request->product, "-") . ')';
            $billTransaction->service_name = $request->product;
            $billTransaction->transaction_ref = $transactionRef;
            $billTransaction->service_icon = $icon;
            $billTransaction->status = $status == "approved" ? 1 : ($status == "pending" ? 0 : ($status == "cancelled" ? 2 : 1));
            $billTransaction->utility_id = 4;
            $billTransaction->token = strlen($data['details']['token']) > 9 ? $data['details']['token'] : $data['details']['units'];
            $billTransaction->trx_status = $data['meta']['status']; 

            $billTransaction->save();

            // make amount readable
            $amount = number_format($request->amount, 2);
            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $billTransaction->created_at->setTimezone($wa_time);

            try {
                FCMService::send(
                    $user->fcm,
                    [
                        'title' => 'Electricity Bill Payment Successfull',
                        'body' => "Your Electricity bill payment with the reference " . $billTransaction->transaction_ref . " was successful. Pleae check app for more details.",
                    ]
                );
            } //catch exception
            catch (Exception $e) {
                Log::error('Message: ' . $e->getMessage());
            }

            try {
                Mail::to($user->email)->send(new \App\Mail\buyPower($user, $amount, $request->meter_no, ucwords($request->product, '-'), $ref, date("h:iA, F jS, Y", strtotime("$time"))));
            } catch (\Exception $e) {
                info($e);
            }

            return response(['success' => 'Power purchased successfully'], 200);
        } else {
            return response(['error' => 'Transaction failed'], 422);
        }
    }

    public function getDataList()
    {
        // get biller code from url query
        $billerCode = request()->billerCode;

        // make sure biller code is not empty
        if (!$billerCode) {
            return response(['error' => 'Biller code is required'], 422);
        }

        $response = Http::withBasicAuth(env('VTPASS_EMAIL'), env('VTPASS_PASSWORD'))->get(env('VTPASS_API') . 'service-variations?serviceID=' . $billerCode);

        // convert response to json
        $response = $response->json();

        return response($response, 200);
    }

    public function getRedDataList($product)
    {

        // make sure product code is not empty
        if (!$product) {
            return response(['error' => 'Product is required'], 422);
        }

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/bills/data/plans/list', ["product" => $product]);

        // convert response to json
        $response = $response->json();

        return response($response, 200);
    }

    public function buyRedAirtime(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'product' => 'required',
            'phone_no' => 'required',
            'amount' => 'required',

        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = auth('api')->user();


        $wallet = $user->wallet;





        if ($request->amount > $wallet->balance) {
            return response(['error' => 'Insufficient funds in Naira wallet'], 422);
        }





        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }

        // check if the amount is below the minimum amount of 50
        if ($request->amount < 50) {
            return response(['error' => 'Minimum amount is 50'], 422);
        }

        // generate unique ref from date time in gmt +1
        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $body = [
            'product' => $request->product,
            'phone_no' => $request->phone_no,
            'amount' => $request->amount,
            'reference' => $transactionRef,
        ];

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/bills/airtime/purchase/create', $body);

        Log::info($response);

        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200) {
            $status = strtolower($data['meta']['status']);
           
            // update wallet balance
            if($status != "cancelled"){
                $wallet->balance = $wallet->balance - $request->amount;
                $wallet->save();
            }


            // add to trx table
            // now update main transaction table
            $ref =    time() . '-' . $user->id;
            switch ($request->product) {
                case "MTN":
                    $icon = '/images/utility/mtn.png';
                    break;
                case "Airtel":
                    $icon = '/images/utility/airtel.png';
                    break;
                case "Glo":
                    $icon = '/images/utility/glo.png';
                    break;
                case "9Mobile":
                    $icon = '/images/utility/9-mobile.png';
                    break;
                default:
                    $icon =  'uploads/images/utility/1653393129.png';
                    break;
            }
            if (str_contains($request->service_id, 'etisalat')) {
                $service = "9-Mobile";
            } else {
                $service = $request->product;
            }


            $billTransaction = new BillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->phone_no;
            $billTransaction->type = 'Airtime Purchase';
            $billTransaction->service_name = $request->product;
            $billTransaction->service_icon = $icon;
            $billTransaction->transaction_ref = $transactionRef;
            $billTransaction->status = $status == "approved" ? 1 : ($status == "pending" ? 0 : ($status == "cancelled" ? 2 : 1));
            $billTransaction->trx_status = $data['meta']['status']; 
            $billTransaction->utility_id = 1;
            $billTransaction->package = ucwords($service);
            $billTransaction->save();


            // make amount readable
            $amount = number_format($request->amount, 2);
            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $billTransaction->created_at->setTimezone($wa_time);


            try {
                FCMService::send(
                    $user->fcm,
                    [
                        'title' => 'Airtime Successfully',
                        'body' => "Your Airtime purchase with the reference " . $billTransaction->id . " was successful. Pleae check app for more details.",
                    ]
                );
            } //catch exception
            catch (Exception $e) {
                Log::error('Message: ' . $e->getMessage());
            }

            try {
                Mail::to($user->email)->send(new \App\Mail\buyAirtime($user, $amount, $request->phone_no, $ref, date("h:iA, F jS, Y", strtotime("$time"))));
            } catch (\Exception $e) {
                info($e);
            }

            return response(['success' => 'Airtime purchased successfully'], 200);
        } else {
            return response(['error' => 'Transaction failed'], 422);
        }
    }

    public function buyAirtime(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'number' => 'required',
            'service_id' => 'required',
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = auth('api')->user();


        $wallet = $user->wallet;
        if ($request->amount > $wallet->balance) {
            return response(['error' => 'Insufficient funds in wallet'], 422);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }

        // generate unique ref from date time in gmt +1
        $ref = Carbon::now()->addHours(1)->format('YmdHis');

        // check if the amount is below the minimum amount of 5
        if ($request->amount < 5) {
            return response(['error' => 'Minimum amount is 5'], 422);
        }
        // create http request with basic auth
        $response = Http::withBasicAuth(env('VTPASS_EMAIL'), env('VTPASS_PASSWORD'))->post(env('VTPASS_API') . 'pay', [
            'phone' => $request->number,
            'amount' => $request->amount,
            'serviceID' => $request->service_id,
            'request_id' => $ref,
        ]);


        $server_output = json_decode($response);

        if ($server_output->content->transactions->status == 'delivered') {
            // update wallet balance
            $wallet->balance = $wallet->balance - $request->amount;
            $wallet->save();

            // add to trx table
            // now update main transaction table
            $ref =    time() . '-' . $user->id;
            switch ($request->service_id) {
                case Utility::NETWORK_MTN:
                    $icon = '/images/utility/mtn.png';
                    break;
                case Utility::NETWORK_AIRTEL:
                    $icon = '/images/utility/airtel.png';
                    break;
                case Utility::NETWORK_GLO:
                    $icon = '/images/utility/glo.png';
                    break;
                case Utility::NETWORK_9MOBILE:
                    $icon = '/images/utility/9-mobile.png';
                    break;
                default:
                    $icon =  'uploads/images/utility/1653393129.png';
                    break;
            }
            if (str_contains($request->service_id, 'etisalat')) {
                $service = "9-Mobile";
            } else {
                $service = $request->service_id;
            }
            // $transactForm = [
            //     'user_id' => $user->id,
            //     'service_id' => 3,
            //     'ngn_amount' => $request->amount,
            //     'transaction_ref' => $ref,
            //     'utility_id' => 1,
            //     'utility_service' => $request->service_id,
            //     'icon' => $icon,
            //     'title' => 'Airtime (' . ucwords($service) . ')',
            //     'status' => 1
            // ];

            // $transaction =  Transaction::create($transactForm);


            $billTransaction = new BillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->number;
            $billTransaction->type = 'Airtime Purchase';
            $billTransaction->service_name = $request->service_id;
            $billTransaction->service_icon = $icon;
            $billTransaction->transaction_ref = $ref;
            $billTransaction->status = 1;
            $billTransaction->utility_id = 1;
            $billTransaction->package = ucwords($service);
            $billTransaction->save();


            // make amount readable
            $amount = number_format($request->amount, 2);
            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $billTransaction->created_at->setTimezone($wa_time);


            try {
                FCMService::send(
                    $user->fcm,
                    [
                        'title' => 'Airtime Successfully',
                        'body' => "Your Airtime purchase with the reference " . $billTransaction->id . " was successful. Pleae check app for more details.",
                    ]
                );
            } //catch exception
            catch (Exception $e) {
                Log::error('Message: ' . $e->getMessage());
            }

            try {
                Mail::to($user->email)->send(new \App\Mail\buyAirtime($user, $amount, $request->number, $ref, date("h:iA, F jS, Y", strtotime("$time"))));
            } catch (\Exception $e) {
                info($e);
            }

            return response(['success' => 'Airtime purchased successfully'], 200);
        } else {
            return response(['error' => 'Transaction failed'], 422);
        }
    }

    public function buyData(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'number' => 'required',
            'variation_code' => 'required',
            'variation_name' => 'required',
            'amount' => 'required',
            'service_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = auth('api')->user();
        $wallet = $user->wallet;
        if ($request->amount > $wallet->balance) {
            return response(['error' => 'Insufficient funds in wallet'], 422);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }

        $ref = Carbon::now()->addHours(1)->format('YmdHis');

        // create http request with basic auth
        $response = Http::withBasicAuth(env('VTPASS_EMAIL'), env('VTPASS_PASSWORD'))->post(env('VTPASS_API') . 'pay', [
            'phone' => $request->number,
            'amount' => $request->amount,
            'variation_code' => $request->variation_code,
            'serviceID' => $request->service_id,
            'billersCode' => $request->number,
            'request_id' => $ref,
        ]);



        $server_output = json_decode($response);

        if ($server_output->content->transactions->status == 'delivered') {
            // update wallet balance
            $wallet->balance = $wallet->balance - $request->amount;
            $wallet->save();

            // Add to trx table
            $ref =    time() . '-' . $user->id;
            switch (explode('-data', $request->service_id)[0]) {
                case Utility::NETWORK_MTN:
                    $icon = '/images/utility/mtn.png';
                    break;
                case Utility::NETWORK_AIRTEL:
                    $icon = '/images/utility/airtel.png';
                    break;
                case Utility::NETWORK_GLO:
                    $icon = '/images/utility/glo.png';
                    break;
                case Utility::NETWORK_9MOBILE:
                    $icon = '/images/utility/9-mobile.png';
                    break;
                default:
                    $icon =  'uploads/images/utility/1653393129.png';
                    break;
            }

            if (str_contains($request->service_id, 'etisalat')) {
                $service = "9-Mobile";
            } else {
                $service = explode('-data', $request->service_id)[0];
            }

            // $transactForm = [
            //     'user_id' => $user->id,
            //     'service_id' => 3,
            //     'ngn_amount' => $request->amount,
            //     'transaction_ref' => $ref,
            //     'utility_id' => 2,
            //     'utility_service' => $request->service_id,
            //     'icon' => $icon,
            //     'title' => 'Data (' . ucwords($service) . ')',
            //     'status' => 1,
            // ];


            // $transaction =  Transaction::create($transactForm);

            $billTransaction = new BillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->number;
            $billTransaction->type = 'Data Bundle';
            $billTransaction->service_name = $request->service_id;
            $billTransaction->service_icon = $icon;
            $billTransaction->utility_id = 1;
            $billTransaction->transaction_ref = $ref;
            $billTransaction->status = 1;
            $billTransaction->package = $request->variation_name;
            $billTransaction->save();

            // make amount readable
            $amount = number_format($request->amount, 2);

            try {
                FCMService::send(
                    $user->fcm,
                    [
                        'title' => 'Data Subscription Successfull',
                        'body' => "Your Data Subscription payment with the reference " . $transaction->transaction_ref . " was successful. Pleae check app for more details.",
                    ]
                );
            } //catch exception
            catch (Exception $e) {
                Log::error('Message: ' . $e->getMessage());
            }

            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $billTransaction->created_at->setTimezone($wa_time);
            // Mail::to($user->email)->send(new \App\Mail\buyDataBundle($user, $amount, $request->number));


            try {
                Mail::to($user->email)->send(new \App\Mail\buyDataBundle($user, $amount, $request->number, $billTransaction->package, $ref, date("h:iA, F jS, Y", strtotime("$time"))));
            } catch (\Exception $e) {
                info($e);
            }
            return response(['success' => 'Data purchased successfully'], 200);
        } else {
            return response(['error' => 'Transaction failed'], 422);
        }
    }

    public function buyRedData(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone_no' => 'required',
            'code' => 'required',
            'variation_name' => 'required',
            'amount' => 'required',
            'product' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = auth('api')->user();
        $wallet = $user->wallet;
        $wallet = $user->wallet;


        if ($request->amount > $wallet->balance) {
            return response(['error' => 'Insufficient funds in Naira wallet'], 422);
        }


        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }

        // generate unique ref from date time in gmt +1
        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $body = [
            'product' => $request->product,
            'phone_no' => $request->phone_no,
            'amount' => $request->amount,
            'reference' => $transactionRef,
            'code' => $request->code,
            'variation_name' => $request->variation_name,
        ];

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/bills/data/plans/purchase/create', $body);

        Log::info($response);

        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200) {
             $status = strtolower($data['meta']['status']);
            // update wallet balance
           if($status != "cancelled"){
                $wallet->balance = $wallet->balance - $request->amount;
                $wallet->save();
            }

            $ref =    time() . '-' . $user->id;
            switch ($request->product) {
                case "MTN":
                    $icon = '/images/utility/mtn.png';
                    break;
                case "Airtel":
                    $icon = '/images/utility/airtel.png';
                    break;
                case "Glo":
                    $icon = '/images/utility/glo.png';
                    break;
                case "9Mobile":
                    $icon = '/images/utility/9-mobile.png';
                    break;
                default:
                    $icon =  'uploads/images/utility/1653393129.png';
                    break;
            }
            if (str_contains($request->service_id, 'etisalat')) {
                $service = "9-Mobile";
            } else {
                $service = $request->product;
            }

            $billTransaction = new BillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->phone_no;
            $billTransaction->type = 'Data Bundle';
            $billTransaction->service_name = $request->product;
            $billTransaction->service_icon = $icon;
            $billTransaction->utility_id = 1;
            $billTransaction->transaction_ref = $transactionRef;
            $billTransaction->status = $status == "approved" ? 1 : ($status == "pending" ? 0 : ($status == "cancelled" ? 2 : 1));
            $billTransaction->trx_status = $data['meta']['status']; 
            $billTransaction->package = $request->variation_name;

            $billTransaction->save();


            // make amount readable
            $amount = number_format($request->amount, 2);

            try {
                FCMService::send(
                    $user->fcm,
                    [
                        'title' => 'Data Subscription Successfull',
                        'body' => "Your Data Subscription payment was successful. Pleae check app for more details.",
                    ]
                );
            } //catch exception
            catch (Exception $e) {
                Log::error('Message: ' . $e->getMessage());
            }

            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $billTransaction->created_at->setTimezone($wa_time);
            // Mail::to($user->email)->send(new \App\Mail\buyDataBundle($user, $amount, $request->number));


            try {
                Mail::to($user->email)->send(new \App\Mail\buyDataBundle($user, $amount, $request->phone_no, $billTransaction->package, $ref, date("h:iA, F jS, Y", strtotime("$time"))));
            } catch (\Exception $e) {
                info($e);
            }
            return response(['success' => 'Data purchased successfully'], 200);
        } else {
            return response(['error' => 'Transaction failed'], 422);
        }
    }

    // create function with utilityList
    public function utilityList()
    {
        // CREATE OBJECT with utility bills constant
        $power = Utility::POWER;
        $tv = Utility::TV;

        $utilityList = [
            'power' => $power,
            'tv' => $tv,
        ];
        return response($utilityList, 200);
    }


    public function getTvPackages()
    {
        // get biller code from url query
        $billerCode = request()->billerCode;

        if (!$billerCode) {
            return response(['error' => 'Biller code is required'], 422);
        }

        $response = Http::withBasicAuth(env('VTPASS_EMAIL'), env('VTPASS_PASSWORD'))->get(env('VTPASS_API') . 'service-variations?serviceID=' . $billerCode);


        $response = $response->json();
        return response($response, 200);
    }

    public function getRedTvPackages($product)
    {

        // make sure product code is not empty
        if (!$product) {
            return response(['error' => 'Product is required'], 422);
        }

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/bills/cable/plans/list', ["product" => $product]);

        // convert response to json
        $response = $response->json();

        return response($response, 200);
    }

    public function buyRedTVSub(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone_no' => 'required',
            'code' => 'required',
            'variation_name' => 'required',
            'smart_card_no' => 'required',
            'customer_name' => 'required',
            'amount' => 'required',
            'product' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = auth('api')->user();
        $wallet = $user->wallet;
        $wallet = $user->wallet;



        if ($request->amount > $wallet->balance) {
            return response(['error' => 'Insufficient funds in Naira wallet'], 422);
        }


        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }

        // generate unique ref from date time in gmt +1
        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $body = [
            'product' => $request->product,
            'phone_no' => $request->phone_no,
            'amount' => $request->amount,
            'reference' => $transactionRef,
            'code' => $request->code,
            'variation_name' => $request->variation_name,
            'customer_name' => $request->customer_name,
            'smart_card_no' => $request->smart_card_no,
        ];

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/bills/cable/plans/purchase/create', $body);

        Log::info($response);

        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200) {
            $status = strtolower($data['meta']['status']);
            // update wallet balance
            if($status != "cancelled"){
                $wallet->balance = $wallet->balance - $request->amount;
                $wallet->save();
            }

            $type = 'TV (' . $request->product . ')';

            $ref =    time() . '-' . $user->id;
            switch ($request->product) {
                case "Dstv":
                    $icon = '/images/utility/dstv.png';
                    break;
                case "Gotv":
                    $icon = '/images/utility/gotv.png';
                    break;
                case "StarTimes":
                    $icon = '/images/utility/startimes.png';
                    break;
                case Utility::CABLE_SHOWMAX:
                    $icon = '/images/utility/showmax.png';
                    break;
                default:
                    $icon =  '/uploads/images/services//1653394130.png';
                    break;
            }
            if (str_contains($request->service_id, 'etisalat')) {
                $service = "9-Mobile";
            } else {
                $service = $request->product;
            }

            $type = 'TV (' . $request->service_id . ')';

            $billTransaction = new BillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->smart_card_no;
            $billTransaction->type = $type;
            $billTransaction->status = $status == "approved" ? 1 : ($status == "pending" ? 0 : ($status == "cancelled" ? 2 : 1));
            $billTransaction->trx_status = $data['meta']['status']; 
            $billTransaction->utility_id = 3;
            $billTransaction->service_icon = $icon;
            $billTransaction->service_name = $request->product;
            $billTransaction->transaction_ref = $transactionRef;
            $billTransaction->package = $request->variation_name;
            $billTransaction->save();

            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $billTransaction->created_at->setTimezone($wa_time);

            try {
                Mail::to($user->email)->send(new \App\Mail\buyTvCable($user, $request->amount, $request->number, strtoupper($request->service_id), $billTransaction->package, $ref, date("h:iA, F jS, Y", strtotime("$time"))));
            } catch (\Exception $e) {
                info($e);
            }

            return response(['success' => 'Tv package purchased successfully'], 200);
            // make amount readable
            $amount = number_format($request->amount, 2);

            try {
                FCMService::send(
                    $user->fcm,
                    [
                        'title' => 'TV Bill Payment Successfull',
                        'body' => "Your TV bill payment with the reference " . $billTransaction->transaction_ref . " was successful. Pleae check app for more details.",
                    ]
                );
            } //catch exception
            catch (Exception $e) {
                Log::error('Message: ' . $e->getMessage());
            }

            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $billTransaction->created_at->setTimezone($wa_time);
            // Mail::to($user->email)->send(new \App\Mail\buyDataBundle($user, $amount, $request->number));


            try {
                Mail::to($user->email)->send(new \App\Mail\buyDataBundle($user, $amount, $request->phone_no, $billTransaction->package, $ref, date("h:iA, F jS, Y", strtotime("$time"))));
            } catch (\Exception $e) {
                info($e);
            }
            return response(['success' => 'Data purchased successfully'], 200);
        } else {
            return response(['error' => 'Transaction failed'], 422);
        }
    }


    public function buyCable(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'number' => 'required',
            'variation_code' => 'required',
            'variation_name' => 'required',
            'amount' => 'required',
            'service_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = auth('api')->user();
        $wallet = $user->wallet;
        if ($request->amount > $wallet->balance) {
            return response(['error' => 'Insufficient funds in wallet'], 422);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }

        $ref = Carbon::now()->addHours(1)->format('YmdHis');

        // create http request with basic auth
        $response = Http::withBasicAuth(env('VTPASS_EMAIL'), env('VTPASS_PASSWORD'))->post(env('VTPASS_API') . 'pay', [
            'phone' => $request->number,
            'amount' => $request->amount,
            'variation_code' => $request->variation_code,
            'serviceID' => $request->service_id,
            'billersCode' => $request->number,
            'request_id' => $ref,
        ]);




        $server_output = json_decode($response);

        if ($server_output->content->transactions->status == 'delivered') {
            // update wallet balance
            $wallet->balance = $wallet->balance - $request->amount;
            $wallet->save();

            // Add to trx table
            $ref =    time() . '-' . $user->id;
            switch ($request->service_id) {
                case Utility::CABLE_DSTV:
                    $icon = '/images/utility/dstv.png';
                    break;
                case Utility::CABLE_GOTV:
                    $icon = '/images/utility/gotv.png';
                    break;
                case Utility::CABLE_STARTIMES:
                    $icon = '/images/utility/startimes.png';
                    break;
                case Utility::CABLE_SHOWMAX:
                    $icon = '/images/utility/showmax.png';
                    break;
                default:
                    $icon =  '/uploads/images/services//1653394130.png';
                    break;
            }
            // $transactForm = [
            //     'user_id' => $user->id,
            //     'service_id' => 3,
            //     'ngn_amount' => $request->amount,
            //     'transaction_ref' => $ref,
            //     'utility_id' => 3,
            //     'utility_service' => $request->service_id,
            //     'icon' => $icon,
            //     'title' => 'TV (' . ucwords($request->service_id) . ')',
            //     'status' => 1
            // ];

            // $transaction =  Transaction::create($transactForm);

            $type = 'TV (' . $request->service_id . ')';

            $billTransaction = new BillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->number;
            $billTransaction->type = $type;
            $billTransaction->status = 1;
            $billTransaction->utility_id = 3;
            $billTransaction->service_icon = $icon;
            $billTransaction->service_name = $request->service_id;
            $billTransaction->transaction_ref = $ref;
            $billTransaction->package = $request->variation_name;
            $billTransaction->save();

            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $billTransaction->created_at->setTimezone($wa_time);

            try {
                Mail::to($user->email)->send(new \App\Mail\buyTvCable($user, $request->amount, $request->number, strtoupper($request->service_id), $billTransaction->package, $ref, date("h:iA, F jS, Y", strtotime("$time"))));
            } catch (\Exception $e) {
                info($e);
            }

            return response(['success' => 'Tv package purchased successfully'], 200);
        } else if ($server_output->content->transactions->status == 'pending') {

            // Add to trx table
            $ref =    time() . '-' . $user->id;
            switch ($request->service_id) {
                case Utility::CABLE_DSTV:
                    $icon = '/images/utility/dstv.png';
                    break;
                case Utility::CABLE_GOTV:
                    $icon = '/images/utility/gotv.png';
                    break;
                case Utility::CABLE_STARTIMES:
                    $icon = '/images/utility/startimes.png';
                    break;
                case Utility::CABLE_SHOWMAX:
                    $icon = '/images/utility/showmax.png';
                    break;
                default:
                    $icon =  '/uploads/images/services//1653394130.png';
                    break;
            }

            // update wallet balance
            $wallet->balance = $wallet->balance - $request->amount;
            $wallet->save();

            $type = 'Tv bill (' . $request->service_id . ')';

            $billTransaction = new BillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->number;
            $billTransaction->type = $type;
            $billTransaction->status = 0;
            $billTransaction->utility_id = 3;
            $billTransaction->service_icon = $icon;
            $billTransaction->service_name = $request->service_id;
            $billTransaction->transaction_ref = $ref;
            $billTransaction->save();

            // make amount readable
            $amount = number_format($request->amount, 2);

            try {
                FCMService::send(
                    $user->fcm,
                    [
                        'title' => 'TV Bill Payment Successfull',
                        'body' => "Your TV bill payment with the reference " . $transaction->transaction_ref . " was successful. Pleae check app for more details.",
                    ]
                );
            } //catch exception
            catch (Exception $e) {
                Log::error('Message: ' . $e->getMessage());
            }

            // \Mail::to($user->email)->send(new \App\Mail\buyTvCable($user, $amount, $request->number, $request->cable));


            return response(['success' => 'Tv bill purchase pending'], 200);
        } else {
            return response(['error' => 'Transaction failed'], 422);
        }
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
     * @param  \App\Http\Requests\StoreUtilityRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function mianStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'prefix' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $imageName = time() . '.' . $request->image->extension();

        $request->image->move(public_path(env('SERVICES_IMAGE_URL') . '/'), $imageName);
        $url = env('SERVICES_IMAGE_URL') . '/' . $imageName;

        Utility::create([
            'name' => $request->name,
            'image' => $url,
            'service_id' => 2,
            'prefix' => $request->prefix,
        ]);

        $response = ["message" => 'Utility servie added'];

        return response($response, 200);
    }

    public function getTransaction($id)
    {
        // $data = GiftCardTransaction::get();
        // $service = Service::where('id', 1)->first();
        $user = auth('api')->user();
        $transaction = BillTransaction::where('id', $id)->first();
        $utility = Utility::where('id', $transaction->utility_id)->first();
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

        $transItem['id'] = $transaction->id;
        $transItem['user_id'] = $transaction->user_id;
        $transItem['icon'] =  env('APP_URL') . $utility->image;
        $transItem['service_id'] = 3;
        $transItem['type'] = $transaction->type;
        $transItem['amount'] = $transaction->amount;
        $transItem['number'] = $transaction->number;
        $transItem['service_icon'] = env('APP_URL') . $transaction->service_icon;
        $transItem['service_name'] = $transaction->service_name;
        $transItem['transaction_ref'] = $transaction->id;
        $transItem['status'] = $status;
        $transItem['package'] = $transaction->package;
        $transItem['utility_id'] = $transaction->utility_id;
        $transItem['utility_name'] = $utility->name;
        $transItem['utility_prefix'] = $utility->prefix;
        $transItem['created_at'] = $transaction->created_at;
        $transItem['token'] = $transaction->token;
        $transItem['trx_status'] = $transaction->trx_status;


        $data = [
            'message' => 'Transaction fetched successfully',
            'transaction' => $transItem,
        ];
        return response($data, 200);
    }

    public function store(StoreUtilityRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Utility  $utility
     * @return \Illuminate\Http\Response
     */
    public function show(Utility $utility)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Utility  $utility
     * @return \Illuminate\Http\Response
     */
    public function edit(Utility $utility)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateUtilityRequest  $request
     * @param  \App\Models\Utility  $utility
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUtilityRequest $request, Utility $utility)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Utility  $utility
     * @return \Illuminate\Http\Response
     */
    public function destroy(Utility $utility)
    {
        //
    }
    
    public function verifyPurchase()
    { 
       $pending_trxs = BillTransaction::where('trx_status', "Pending")->get()->sortBy('created_at');

       foreach($pending_trxs as $trx){
           
           $type = strtolower($trx->type);
           
            $user = User::where("id", $trx->user_id)->first();
            $wallet = $user->wallet;
            
           
           $body = [
               "reference"=> $trx->transaction_ref
               ];
           
           $request = "https://api.live.redbiller.com/1.0/bills/disco/purchase/status";
           
           if(str_contains($type, "airtime")){
               $request = "https://api.live.redbiller.com/1.0/bills/airtime/purchase/status";
           }else if(str_contains($type, "data")){
               $request = "https://api.live.redbiller.com/1.0/bills/data/plans/purchase/status";
           } else if(str_contains($type, "tv")){
               $request = "https://api.live.redbiller.com/1.0/bills/cable/plans/purchase/status";
           }else if(str_contains($type, "power")){
               $request = "https://api.live.redbiller.com/1.0/bills/disco/purchase/status";
           }
           
           $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post($request, $body);

        Log::info($response);

        $data = $response->json();
        
         if ($data['status'] == true && $data['response'] == 200) {
             $status = strtolower($data['meta']['status']);
             
             $trx->update([
                 "status" => $status == "approved" ? 1 : ($status == "pending" ? 0 : ($status == "cancelled" ? 2 : 1)),
                 "trx_status" => $data['meta']['status'],
                 "token" => $data['details']['token'],
                 ]);
                 
            if($status == "cancelled"){
                
                    $wallet->balance = $wallet->balance + $trx->amount;
                    $wallet->save();
                
                try {
                FCMService::send(
                    $user->fcm,
                    [
                        'title' => 'Transaction Failed',
                        'body' => "Your utility bill payment with the reference " . $trx->transaction_ref . " has failed and your funds of NGN". $trx->amount ." has been reversed. Apologies for this inconvinience. Please check app for more details.",
                    ]
                );
                } //catch exception
                catch (Exception $e) {
                    Log::error('Message: ' . $e->getMessage());
                }
                
            }
         }
       }
    }
}
