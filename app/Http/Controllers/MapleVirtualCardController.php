<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\MapleCustomer;
use App\Models\MapleVirtualCard;
use Illuminate\Support\Facades\Log;
use App\Services\VirtualCardService;
use App\Models\VirtualCardTransaction;
use App\Models\MapleVirtualCardReference;
use App\Models\Wallet;
use Illuminate\Support\Facades\Validator;

class MapleVirtualCardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth('api')->user();

        $customer = MapleCustomer::where("user_id", $user->id)->first();

        $card = MapleVirtualCard::where("user_id", $user->id)->first();

        if ($customer == null) {
            return response(
                [
                    'error' => false,
                    'message' => "Cards fetchd successfully",
                    'data' => []
                ],
                200
            );
        }

        $u_c = VirtualCardService::checkMyActiveCards($user);

        if ((!$u_c || $u_c == null || $u_c == false) && ($customer == null || $card == null)) {
            $response = response()->json([
                'error' => true,
                'message' => "Process failed, please try again later"
            ], 423);


            Log::error($response);

            return response(["message" => "fff"], 403);
        }

        return response(
            [
                'error' => false,
                'message' => "Cards fetchd successfully",
                'data' => MapleVirtualCard::where('user_id', $user->id)->get()
            ],
            200
        );
    }
    public function createCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "brand" => "required",
            "street" => "required",
            "city" => "required",
            "state" => "required",
            "country" => "required",
            "phone_country_code" => "required",
            "phone_number" => "required",
        ]);

        if ($validator->fails()) {
            // get summary
            $errors = $validator->errors();
            $errorMessages = [];

            foreach ($errors->all() as $message) {
                $errorMessages[] = $message;
            }

            $summary = implode(" \n", $errorMessages);

            return response(
                [
                    'error' => true,
                    'message' => $summary
                ],
                422
            );
        }

        $user = auth('api')->user();

        $u_c = VirtualCardService::checkMyActiveCards($user);

        $existing_cards = MapleVirtualCard::where("user_id", $user->id)->where("status", "ACTIVE")->get();

        if ($existing_cards->count() > 0) {
            return response(
                [
                    'error' => false,
                    'message' => "Virtual card created successfully"
                ],
                200
            );
        }

        $verification = Verification::where("user_id", $user->id)->where("status", 1)->first();

        $customer = MapleCustomer::where("user_id", $user->id)->first();

        if (!$verification || $verification == null) {
            return response(
                [
                    'error' => true,
                    'message' => "Please complete your KYC verification to be able to create a virtual card"
                ],
                422
            );
        }

        if (!$customer || $customer == null) {
            $c_c =  VirtualCardService::createCustomer($user);

            if (!$c_c || $c_c == null || $c_c == false) {
                return response(
                    [
                        'error' => true,
                        'message' => "Process failed please try again later"
                    ],
                    423
                );
            }

            $customer = MapleCustomer::where("user_id", $user->id)->first();
        }

        $payload = $request->toArray();

        if ($customer->tier < 1) {

            $u_c =   VirtualCardService::upgradeCustomer($user, $payload);

            if (!$u_c || $u_c == null || $u_c == false) {
                return response(
                    [
                        'error' => true,
                        'message' => "Process failed please try again later"
                    ],
                    423
                );
            }

            $customer = MapleCustomer::where("user_id", $user->id)->first();
        }

        $c_v_c = VirtualCardService::createCard($user, $payload);

        if (!$c_v_c || $c_v_c == null || $c_v_c == false) {
            return response()->json([
                'error' => true,
                'message' => "Process failed please try again later"
            ], 423);
        }
        return response(
            [
                'error' => false,
                'message' => "Virtual card created successfully"
            ],
            200
        );
    }

    public function getCards(Request $request)
    {
        $user = auth('api')->user();

        $customer = MapleCustomer::where("user_id", $user->id)->first();

        $card = MapleVirtualCard::where("user_id", $user->id)->first();

        if ($customer == null || $card == null) {
            return response()->json([
                'error' => true,
                'message' => "Process failed please try again later"
            ], 423);
        }

        $u_c = VirtualCardService::updateMyCard($user);

        if (!$u_c || $u_c == null || $u_c == false) {
            return response()->json([
                'error' => true,
                'message' => "Process failed please try again later"
            ], 423);
        }

        return response()->json([
            'error' => true,
            'message' => "Process failed please try again later"
        ], 423);
    }

    public function getData(Request $request)
    {

        $user = auth('api')->user();

        $customer = MapleCustomer::where("user_id", $user->id)->first();

        // $card = MapleVirtualCard::where("user_id", $user->id)->first();

        if ($customer == null) {
            return response(
                [
                    'error' => false,
                    'message' => "Cards fetchd successfully",
                    'data' => [
                        "card" => null,
                        "transactions" => VirtualCardTransaction::where("user_id", $user->id)->get(),
                    ]
                ],
                200
            );
        }

        $u_c = VirtualCardService::checkMyActiveCards($user);

        // if (!$u_c || $u_c == null || $u_c == false) {
        //     $response = response()->json([
        //         'error' => true,
        //         'message' => "Process failed, please try again later"
        //     ], 423);


        //     // dd($response);

        //     return response(["message" => "fff"], 403);
        // }

        return response(
            [
                'error' => false,
                'message' => "Cards fetchd successfully",
                'data' => [
                    "card" => MapleVirtualCard::where("user_id", $user->id)->where("status", "ACTIVE")->first(),
                    "transactions" => VirtualCardTransaction::where("user_id", $user->id)->get(),
                ]
            ],
            200
        );
    }


    public function getRate()
    {
        $user = auth('api')->user();

        $rates = VirtualCardService::getConversionRate();

        return response(
            [
                "message" => $rates ?  "" : "error encountered updating rates",
                "data" => $rates,
            ],
            $rates ? 200 : 500
        );
    }

    public function webhook(Request $request)
    {

        Log::info($request);
        if ($request->event == "issuing.created.successful") {
            $reference = MapleVirtualCardReference::where("reference", $request->reference)->first();
            $old_card = MapleVirtualCard::where("maple_id", $request->card['id'])->first();
            if ($old_card && $old_card = ! null) {
                return response(
                    [
                        'error' => false,
                        'message' => "OK"
                    ],
                    200
                );
            }
            MapleVirtualCard::create([
                'user_id' => $reference != null ?  $reference->user_id : 0,
                'customer_id' => $reference != null ?  $reference->maple_id : "xxx",
                'maple_id' => $request->card['id'],
                'name' => $request->card['name'], // Cardholder's name
                'masked_pan' => $request->card['masked_pan'], // Masked PAN
                'status' => $request->card['status'],  // ['ACTIVE', 'INACTIVE', 'BLOCKED', 'EXPIRED'], // Card status
                'type' => $request->card['type'], // ['VIRTUAL', 'PHYSICAL'], // Card type
                'issuer' => $request->card['issuer'], // Issuer of the card
                'currency' => $request->card['currency'], // Currency (ISO code)
                'balance' => $request->card['balance'], // lowest denomination; Kobo or Cents", // Balance with precision
                'auto_approve' => $request->card['auto_approve'], // Auto approve flag
            ]);
            return response(
                [
                    'error' => false,
                    'message' => "OK"
                ],
                200
            );
        }

        if ($request->event == "issuing.transaction") {

            switch ($request->type) {
                case "FUNDING":
                    $usd_amount = $request->amount;
                    VirtualCardTransaction::updateOrCreate([
                        "reference" => $request->reference,
                    ], [
                        'usd_amount' => $usd_amount / 100,
                        'is_termination' => $request->is_termination,
                        'type'  => $request->type,
                        'maple_status' => $request->status,
                        'status' => $request->status == 'SUCCESSFUL' || $request->status == 'SUCCESS' ? 1 : 4,
                        'maple_data' => json_encode($request->toArray()),
                    ]);
            }
        }
    }

    public function fundMyCard(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            "amount" => "required",
        ]);

        if ($validator->fails()) {
            // get summary
            $errors = $validator->errors();
            $errorMessages = [];

            foreach ($errors->all() as $message) {
                $errorMessages[] = $message;
            }

            $summary = implode(" \n", $errorMessages);

            return response(
                [
                    'error' => true,
                    'message' => $summary
                ],
                422
            );
        }


        $rates = VirtualCardService::getConversionRate();
        $vcard = MapleVirtualCard::where("user_id", $user->id)->where("status", "ACTIVE")->first();

        if ($rates) {
            $usd_num_fee = $rates['ngn_2_usd']['charge_num'];
            $usd_p_fee = (($rates['ngn_2_usd']['charge_percentage']) / 100) * $request->amount;

            $ngn_num_fee = $usd_num_fee * (1 / ($rates['ngn_2_usd']['rate']));
            $ngn_p_fee = $usd_p_fee * (1 / ($rates['ngn_2_usd']['rate']));

            $ngn_amount = ($request->amount + $usd_num_fee + $usd_p_fee) * (1 / $rates['ngn_2_usd']['rate']);

            // charge user
            $user_wallet = Wallet::where('user_id', $vcard->user_id)->first();

            if ($ngn_amount > $user_wallet->balance) {
                return response([
                    'message' => "Insufficient balance",
                    'error' => true
                ], 429);
            } else {
                $user_wallet->update([
                    'balance' => $user_wallet->balance - $ngn_amount
                ]);
            }

            $data =  VirtualCardService::fundCard($vcard->maple_id, floor($request->amount * 100));



            $vcard_trx =  VirtualCardTransaction::create([
                'usd_amount' => $request->amount,
                'usd_fee' => ($usd_num_fee + $usd_p_fee),
                'fx_rate' => $rates['ngn_2_usd']['rate'],
                'ngn_amount' => $ngn_amount,
                'ngn_fee' => ($ngn_num_fee + $ngn_p_fee),
                'maple_card_id' => $vcard->maple_id,
                'reference' => $data ? $data->data->id : "xxx",
                'type' => 'FUNDING',
                'user_id' => $vcard->user_id,
                'payment_data' => json_encode($request->headers->all())
            ]);
        }
    }
}
