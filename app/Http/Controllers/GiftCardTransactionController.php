<?php

namespace App\Http\Controllers;

use Exception;
use DateTimeZone;
use App\Models\User;
use App\Models\country;
use App\Models\Service;
use App\Models\GiftCard;
use App\Models\CardRange;
use App\Models\Currencies;
use App\Models\Transaction;
use Facade\FlareClient\Api;
use App\Services\FCMService;
use Illuminate\Http\Request;
use App\Models\GeneralCountry;
use App\Models\GiftCardCategory;
use App\Models\GiftCardsCountry;
use App\Models\GiftCardTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreGiftCardTransactionRequest;
use App\Http\Requests\UpdateGiftCardTransactionRequest;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class GiftCardTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth('api')->user();
        $data = $user->giftcardTransactions()->get();
        $service = Service::where('id', 1)->first();
        $giftcardTransaction = [];
        foreach ($data as $transact) {
            $transaction = Transaction::where('id', $transact->transaction_id)->first();
            $giftcard = GiftCard::where('id', $transact->gift_card_id)->first();

            switch ($transact->status) {
                case 0:
                    $status = 'Pending';
                    break;
                case 1:
                    $status = 'Completed';
                    break;
                case 2:
                    $status = 'Failed';
                    break;

                default:
                    $status = 'Pending';
                    break;
            }

            $item['id'] = $transact->id;
            $item['user_id'] = $transact->user_id;
            $item['card_value'] = $transact->card_value;
            $item['usd_rate'] = $transact->rate;
            $item['ngn_amount'] = $transaction->ngn_amount;
            $item['icon'] = env('APP_URL') . $giftcard->brand_logo;
            $item['status'] = $status;
            $item['created_at'] = $transact->created_at->diffForHumans();

            $giftcardTransaction[] = $item;
        }
        return response($giftcardTransaction, 200);
    }

    public function AdminIndex()
    {
        $data = GiftCardTransaction::get();
        $service = Service::where('id', 1)->first();
        $giftcardTransaction = [];
        foreach ($data as $transact) {
            $transaction = Transaction::where('id', $transact->transaction_id)->first();
            $giftcard = GiftCard::where('id', $transact->gift_card_id)->first();

            switch ($transact->status) {
                case 0:
                    $status = 'Pending';
                    break;
                case 1:
                    $status = 'Failed';
                    break;
                case 2:
                    $status = 'Completed';
                    break;

                default:
                    $status = 'Pending';
                    break;
            }

            $item['id'] = $transact->id;
            $item['user_id'] = $transact->user_id;
            $item['card_value'] = $transact->card_value;
            $item['usd_rate'] = $transact->rate;
            $item['ngn_amount'] = $transaction->ngn_amount;
            $item['icon'] = env('APP_URL') . $giftcard->brand_logo;
            $item['status'] = $status;
            $item['created_at'] = $transact->created_at->diffForHumans();

            $giftcardTransaction[] = $item;
        }
        return response($giftcardTransaction, 200);
    }

    public function getTransaction($id)
    {
        // $data = GiftCardTransaction::get();
        // $service = Service::where('id', 1)->first();
        $user = auth('api')->user();
        $giftcardTransaction = GiftCardTransaction::where('id', $id)->first();
        $giftcardType = GiftCard::where('id', $giftcardTransaction->gift_card_id)->first();
        $cardRange = CardRange::withTrashed()->where('id', $giftcardTransaction->card_range_id)->first();
        $giftcardCountry = GiftCardsCountry::withTrashed()->where('id', $giftcardTransaction->gift_card_country_id)->first();
        $country = country::where('id', $giftcardCountry->country_id)->first();
        $category = GiftCardCategory::withTrashed()->where('id', $giftcardTransaction->gift_card_category_id)->first();
        $generalCountry = GeneralCountry::where("countryCode", strtoupper($country->iso))->first();
        $currency = Currencies::where("code", $generalCountry->currencyCode)->first();

        $transItem = [];

        // Status String
        switch ($giftcardTransaction->status) {
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

        $transItem['id'] = $giftcardTransaction->id;
        $transItem['user_id'] = $giftcardTransaction->user_id;
        $transItem['icon'] =  env('APP_URL') . $giftcardType->brand_logo;
        $transItem['gift_card_image'] =  env('APP_URL') . $giftcardType->image;
        $transItem['service_id'] = 1;
        $transItem['card_value'] = $giftcardTransaction->card_value;
        $transItem['usd_rate'] = $giftcardTransaction->rate;
        $transItem['ngn_amount'] = $giftcardTransaction->rate * $giftcardTransaction->card_value;
        $transItem['transaction_ref'] = $giftcardTransaction->id;
        $transItem['status'] = $status;
        $transItem['giftcard_type'] = $giftcardType->title;
        if ($giftcardTransaction->second_proof) {
            $transItem['second_proof'] = env('APP_URL')  . $giftcardTransaction->second_proof;
        }
        if ($giftcardTransaction->third_proof) {
            $transItem['third_proof'] = env('APP_URL')  . $giftcardTransaction->third_proof;
        }
        $transItem['proof'] = (str_contains($giftcardTransaction->proof, "https://") ? "" : env('APP_URL')) . $giftcardTransaction->proof;
        $transItem['category'] = $category->title;
        $transItem['note'] = $giftcardTransaction->note;
        $transItem['currency'] = $currency;
        $transItem['rejected_reason'] = $giftcardTransaction->rejected_reason;
        $transItem['range'] = [
            'max' => $cardRange->max,
            'min' => $cardRange->min,
            'rate' => $giftcardTransaction->rate,
        ];
        $transItem['country'] = $country->name;
        $transItem['iso'] = $country->iso;

        $transItem['created_at'] = $giftcardTransaction->created_at;
        $transItem['response_image'] = $giftcardTransaction->response_image;
        $transItem['proofs'] = json_decode($giftcardTransaction->proofs);
        $transItem['qty'] = $giftcardTransaction->qty;
        $transItem['approved_qty'] = $giftcardTransaction->approved_qty;


        $data = [
            'message' => 'Transaction fetched successfully',
            'transaction' => $transItem,
        ];
        return response($data, 200);
    }

    public function adminGetTransaction($id)
    {
        // $data = GiftCardTransaction::get();
        // $service = Service::where('id', 1)->first();
        $user = auth('api')->user();
        $giftcardTransaction = GiftCardTransaction::where('id', $id)->first();
        $giftcardType = GiftCard::where('id', $giftcardTransaction->gift_card_id)->first();
        $cardRange = CardRange::withTrashed()->where('id', $giftcardTransaction->card_range_id)->first();
        $giftcardCountry = GiftCardsCountry::withTrashed()->where('id', $giftcardTransaction->gift_card_country_id)->first();
        $country = country::where('id', $giftcardCountry->country_id)->first();
        $category = GiftCardCategory::withTrashed()->where('id', $giftcardTransaction->gift_card_category_id)->first();
        $generalCountry = GeneralCountry::where("countryCode", strtoupper($country->iso))->first();
        $currency = Currencies::where("code", $generalCountry->currencyCode)->first();

        // Status String
        switch ($giftcardTransaction->status) {
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

        $transItem['id'] = $giftcardTransaction->id;
        $transItem['user_id'] = $giftcardTransaction->user_id;
        $transItem['icon'] =  env('APP_URL') . $giftcardType->brand_logo;
        $transItem['service_id'] = $giftcardTransaction->service_id;
        $transItem['card_value'] = $giftcardTransaction->card_value;
        $transItem['usd_rate'] = $giftcardTransaction->rate;
        $transItem['ngn_amount'] = $giftcardTransaction->rate * $giftcardTransaction->card_value;
        $transItem['transaction_ref'] = $giftcardTransaction->id;
        $transItem['status'] = $status;
        $transItem['giftcard_type'] = $giftcardType->title;
        $transItem['proof'] = env('APP_URL')  . $giftcardTransaction->proof;
        $transItem['receipt_availability'] = $category->title;
        if ($giftcardTransaction->second_proof) {
            $transItem['second_proof'] = env('APP_URL')  . $giftcardTransaction->second_proof;
        }
        if ($giftcardTransaction->third_proof) {
            $transItem['third_proof'] = env('APP_URL')  . $giftcardTransaction->third_proof;
        }
        $transItem['category'] = $category->title;
        $transItem['currency'] = $currency->symbol;
        $transItem['note'] = $giftcardTransaction->note;
        $transItem['rejected_reason'] = $giftcardTransaction->rejected_reason;
        $transItem['approved_by'] = $giftcardTransaction->approved_by;
        $transItem['rejected_by'] = $giftcardTransaction->rejected_by;
        $transItem['range'] = [
            'max' => $cardRange->max,
            'min' => $cardRange->min,
            'rate' => $giftcardTransaction->rate,
        ];
        $transItem['country'] = $country->name;
        $transItem['iso'] = $country->iso;

        $transItem['created_at'] = $giftcardTransaction->created_at;
        $transItem['updated_at'] = $giftcardTransaction->updated_at;
        $transItem['created_at'] = $giftcardTransaction->created_at;
        $transItem['response_image'] = $giftcardTransaction->response_image;
        $transItem['proofs'] = json_decode($giftcardTransaction->proofs);
        $transItem['qty'] = $giftcardTransaction->qty;
        $transItem['approved_qty'] = $giftcardTransaction->approved_qty;




        $data = [
            'message' => 'Transaction fetched successfully',
            'transaction' => $transItem,
        ];
        return response($data, 200);
    }


    public function approveTransaction(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'qty' => 'required',
            'remark' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $giftcardTransaction = GiftCardTransaction::where('id', $request->id)->first();
        if ($giftcardTransaction->status > 0) {
            return response(['errors' => ['Transaction status cannot be updated.']], 422);
        }

        if ($request->qty > $giftcardTransaction->qty) {
            return response(['errors' => ['Transaction quantity is invalid.']], 422);
        }

        $uploadedFileUrl = null;
        if ($request->response_image) {

            $uploadedFileUrl = null;
            try {
                $path = $request->file('response_image')->getRealPath();
            } catch (Exception $e) {
                Log::error($e);
            }
            try {
                $uploadedFileUrl = cloudinary()->upload($request->file('response_image')->getRealPath())->getSecurePath();
            } catch (Exception $e) {
                Log::error($e);
                return response(
                    [
                        'error' => true,
                        'message' => "could not upload image"
                    ],
                    422
                );
            }
        }


        $form = [
            'status' => 1,
            'approved_by' => $user->id,
            'rejected_by' => null,
            'rejected_reason' => $request->remark,
            'response_image' => $uploadedFileUrl,
            'approved_qty' => $request->qty,
        ];

        $trxUser = User::where('id', $giftcardTransaction->user_id)->first();

        GiftCardTransaction::where('id', $giftcardTransaction->id)->update($form);
        $response = ["message" => 'Transaction approved'];
        $range = CardRange::withTrashed()->where('id', $giftcardTransaction->card_range_id)->first();
        // $giftcard_category = GiftCardCategory::where('id', $giftcardTransaction->gift_card_category_id)->first();
        $giftcard = GiftCard::where('id', $giftcardTransaction->gift_card_id)->first();
        $giftcountry = GiftCardsCountry::withTrashed()->where("id", $giftcardTransaction->gift_card_country_id)->first();
        $country = Country::where("id", $giftcountry->country_id)->first();
        $generalCountry = GeneralCountry::where("countryCode", strtoupper($country->iso))->first();
        $currency = Currencies::where("code", $generalCountry->currencyCode)->first();
        $category = GiftCardCategory::withTrashed()->where("id", $giftcardTransaction->gift_card_category_id)->first();


        $wallet = $trxUser->Wallet()->select('balance')->first();

        $walletForm =  [
            'balance' => $wallet->balance + ($giftcardTransaction->card_value * $giftcardTransaction->rate * ($request->qty ?? 1)),
        ];

        $trxUser->Wallet()->update($walletForm);

        try {
            FCMService::send(
                $trxUser->fcm,
                [
                    'title' =>  $request->qty < $giftcardTransaction->qty ? 'Some of your submitted giftcards have been  approved' : 'GiftCard Transaction Successfull' ,
                    'body' => $request->qty < $giftcardTransaction->qty ? "Some of the giftcards submitted under the transaction with reference #" . $request->id . " were successful. Please check app for more details on why others were rejected." : "Your GiftCard transaction with the reference #" . $request->id . " was successful. Please check app for more details.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }


        $wa_time = new DateTimeZone('Africa/Lagos');
        $time = $giftcardTransaction->created_at->setTimezone($wa_time);


        // Handle Referrer
        $referrer_reward =  RewardWalletTransaction::where("referred_user_id", $trxUser->id)->where("status", 0)->first();
        Log::info("referrer_reward => " . $referrer_reward);
        if ($referrer_reward) {
            $referr = User::where("id", $referrer_reward->user_id)->first();
            Log::info("referr => " . $referr);
            if ($referr) {
                $referrer_reward->update([
                    "status" => 1
                ]);

                $referrer_reward_wallet = RewardWallet::where("user_id", $referrer_reward->user_id)->first();
                Log::info("referrer_reward_wallet => " . $referrer_reward_wallet);
                $referrer_reward_wallet->balance = $referrer_reward_wallet->balance + $referrer_reward->amount;
                $referrer_reward_wallet->save();

                try {
                    FCMService::send(
                        $referr->fcm,
                        [
                            'title' => $referrer_reward->amount . ' Cash Reward Earned ',
                            'body' => "You just earned " . $referrer_reward->amount .  " cash reward from a successful referral.",
                        ]
                    );
                } //catch exception
                catch (Exception $e) {
                    Log::error('Message: ' . $e->getMessage());
                }
            }
        }

        $emailData = [
            'name' => $trxUser->first_name,
            'ngn_amount' => number_format(($giftcardTransaction->card_value * $giftcardTransaction->rate * ($request->qty ?? 1)), 2),
            'card_value' => $currency->symbol . number_format($giftcardTransaction->card_value, 2),
            'usd_rate' => $giftcardTransaction->rate,
            'card_range' => $currency->symbol . $range->min . " - " . $currency->symbol . $range->max,
            'status' => 'Approved' . $request->qty != $giftcardTransaction->qty ? ($request->qty . " of " . $giftcardTransaction->qty) : "",
            'transaction_ref' => $giftcardTransaction->id,
            'giftcard' => $giftcard->title,
            'country' => $country->name,
            'receipt_type' => $category->title,
            'time' => date("h:iA, F jS, Y", strtotime("$time")),

        ];

        try {
            Mail::to($trxUser->email)->send(new \App\Mail\approveGiftcardTransaction($emailData));
        } catch (\Exception $e) {
            info($e);
        }

        return response($response, 200);
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
        $giftcardTransaction = GiftCardTransaction::where('id', $request->id)->first();

        if (empty($giftcardTransaction)) {
            return response(['errors' => ['Transaction not found']], 422);
        } else if ($giftcardTransaction->status > 0) {
            return response(['errors' => ['Transaction cannot be cancelled.']], 422);
        }

        // save transaction status to cancelled
        GiftCardTransaction::where('id', $request->id)->update([
            'status' => 3
        ]);

        try {
            FCMService::send(
                $user->fcm,
                [
                    'title' => 'GiftCard Transaction Cancelled',
                    'body' => "Your GiftCard transaction with the reference #" . $request->id . " was cancelled successfully.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }


        // return response with message and data
        return response(['message' => 'Transaction cancelled successfully'], 200);
    }


    public function rejectTransaction(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'rejected_reason' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $giftcardTransaction = GiftCardTransaction::where('id', $request->id)->first();
        if ($giftcardTransaction->status > 0) {
            return response(['errors' => ['Transaction status cannot be updated.']], 422);
        }
        $form = [
            'status' => 2,
            'approved_by' => null,
            'rejected_by' => $user->id,
            'rejected_reason' => $request->rejected_reason,
        ];

        $trxUser = User::where('id', $giftcardTransaction->user_id)->first();

        GiftCardTransaction::where('id', $giftcardTransaction->id)->update($form);
        $response = ["message" => 'Transaction rejected'];
        $range = CardRange::withTrashed()->where('id', $giftcardTransaction->card_range_id)->first();
        // $giftcard_category = GiftCardCategory::where('id', $giftcardTransaction->gift_card_category_id)->first();
        $giftcard = GiftCard::where('id', $giftcardTransaction->gift_card_id)->first();
        $giftcountry = GiftCardsCountry::withTrashed()->where("id", $giftcardTransaction->gift_card_country_id)->first();
        $country = Country::where("id", $giftcountry->country_id)->first();
        $generalCountry = GeneralCountry::where("countryCode", strtoupper($country->iso))->first();
        $currency = Currencies::where("code", $generalCountry->currencyCode)->first();
        $category = GiftCardCategory::withTrashed()->where("id", $giftcardTransaction->gift_card_category_id)->first();

        try {
            FCMService::send(
                $trxUser->fcm,
                [
                    'title' => 'GiftCard Transaction Failed',
                    'body' => "Your GiftCard transaction with the reference #" . $giftcardTransaction->id . " has failed unfortunately. Pleae check app for more details.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }

        $wa_time = new DateTimeZone('Africa/Lagos');
        $time = $giftcardTransaction->created_at->setTimezone($wa_time);



        $emailData = [
            'name' => $trxUser->first_name,
            'ngn_amount' => number_format(($giftcardTransaction->card_value * $giftcardTransaction->rate), 2),
            'card_value' => $currency->symbol . number_format($giftcardTransaction->card_value, 2),
            'usd_rate' => $giftcardTransaction->rate,
            'card_range' => $currency->symbol . $range->min . " - " . $currency->symbol . $range->max,
            'status' => 'In Review',
            'transaction_ref' => $giftcardTransaction->id,
            'giftcard' => $giftcard->title,
            'country' => $country->name,
            'receipt_type' => $category->title,
            'reason' => $request->rejected_reason,
            'time' => date("h:iA, F jS, Y", strtotime("$time")),

        ];

        try {
            Mail::to($trxUser->email)->send(new \App\Mail\failGiftcardTransaction($emailData));
        } catch (\Exception $e) {
            info($e);
        }



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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreGiftCardTransactionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function makeTransaction(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'gift_card_country_id' => 'required',
            'gift_card_id' => 'required',
            'gift_card_category_id' => 'required',
            'card_range_id' => 'required',
            'card_value' => 'required',
            // 'proofs' => 'required|image|mimes:jpeg,png,jpg',
            'proofs' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $range = CardRange::where('id', $request->card_range_id)->first();
        $giftcard_category = GiftCardCategory::where('id', $request->gift_card_category_id)->first();
        $giftcard = GiftCard::where('id', $request->gift_card_id)->first();
        $giftcountry = GiftCardsCountry::where("id", $request->gift_card_country_id)->first();
        $country = Country::where("id", $giftcountry->country_id)->first();
        $generalCountry = GeneralCountry::where("countryCode", strtoupper($country->iso))->first();
        $currency = Currencies::where("code", $generalCountry->currencyCode)->first();
        $category = GiftCardCategory::where("id", $request->gift_card_category_id)->first();


        // // now update main transaction table
        $ref =    time() . '-' . $user->id;


        // Cloudinary

        $uploadedFilesUrls = [];
        foreach ($request->file('proofs') as $key => $file) {
            $uploadedFileUrl = Cloudinary::upload($file->getRealPath())->getSecurePath();
            $uploadedFilesUrls[] = $uploadedFileUrl;
        }

        // Log::info(json_encode($uploadedFilesUrls));
        // Log::info($uploadedFilesUrls[0]);
        // Log::info(json_decode(json_encode($uploadedFilesUrls)));
        // return response(['errors' => "images uploaded"], 422);

        $date_id = date("Y-m-d H:i:s");

        $form = [
            'user_id' => $user->id,
            'service_id' => 1,
            'gift_card_id' => $request->gift_card_id,
            'gift_card_country_id' => $request->gift_card_country_id,
            'gift_card_category_id' => $request->gift_card_category_id,
            'card_range_id' => $request->card_range_id,
            'rate' => $giftcard_category->amount,
            'card_value' => $request->card_value,
            'proof' => $uploadedFilesUrls[0],
            'proofs' => json_encode($uploadedFilesUrls),
            'note' => $request->note,
            'qty' => $request->qty ?? 1,
        ];

        $giftcard_data = GiftCard::where("id", $request->gift_card_id)->first();

        $transaction =  GiftCardTransaction::create($form);


        $response = ["message" => 'Transaction completed'];

        try {
            FCMService::send(
                $user->fcm,
                [
                    'title' => '😎 GiftCard Transaction Submitted',
                    'body' => "Your " . $giftcard_data->title . " GiftCard transaction was submitted.",
                ]
            );
            FCMService::sendToAdmins(
                [
                    'title' => 'New GiftCard Transaction',
                    'body' => "There's a new " . $giftcard_data->title . " GiftCard transaction submitted.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }

        $wa_time = new DateTimeZone('Africa/Lagos');
        $time = $transaction->created_at->setTimezone($wa_time);



        $emailData = [
            'name' => $user->first_name,
            'ngn_amount' => number_format(($transaction->card_value * $transaction->rate), 2),
            'card_value' => $currency->symbol . number_format($transaction->card_value, 2),
            'usd_rate' => $transaction->rate,
            'card_range' => $currency->symbol . $range->min . " - " . $currency->symbol . $range->max,
            'status' => 'In Review',
            'transaction_ref' => $transaction->id,
            'giftcard' => $giftcard->title,
            'country' => $country->name,
            'receipt_type' => $category->title,
            'time' => date("h:iA, F jS, Y", strtotime("$time")),

        ];

        try {
            Mail::to($user->email)->send(new \App\Mail\newGiftcardTransaction($emailData));
        } catch (\Exception $e) {
            info($e);
        }




        return response($response, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\GiftCardTransaction  $giftCardTransaction
     * @return \Illuminate\Http\Response
     */
    public function show(GiftCardTransaction $giftCardTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\GiftCardTransaction  $giftCardTransaction
     * @return \Illuminate\Http\Response
     */
    public function edit(GiftCardTransaction $giftCardTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateGiftCardTransactionRequest  $request
     * @param  \App\Models\GiftCardTransaction  $giftCardTransaction
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateGiftCardTransactionRequest $request, GiftCardTransaction $giftCardTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\GiftCardTransaction  $giftCardTransaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(GiftCardTransaction $giftCardTransaction)
    {
        //
    }
}
