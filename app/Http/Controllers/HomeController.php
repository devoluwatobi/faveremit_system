<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Config;
use App\Models\Crypto;
use App\Models\Wallet;
use App\Models\country;
use App\Models\Service;
use App\Models\Utility;
use App\Models\GiftCard;
use App\Models\CardRange;
use App\Models\PromoCode;
use App\Models\Currencies;
use App\Models\UserDevice;
use App\Models\BankDetails;
use App\Models\Transaction;
use Facade\FlareClient\Api;
use App\Models\RewardWallet;
use App\Services\FCMService;
use Illuminate\Http\Request;
use App\Models\SpaceTransfer;
use App\Models\GeneralCountry;
use App\Models\BannerPromotion;
use App\Models\BillTransaction;
use App\Models\FundTransaction;
use App\Models\GiftCardCategory;
use App\Models\GiftCardsCountry;
use App\Models\MapleVirtualCard;
use App\Models\BettingTransaction;
use App\Models\WalletTransactions;
use Illuminate\Support\Facades\DB;
use App\Models\FundTransferRequest;
use App\Models\GiftCardTransaction;
use function Laravel\Prompts\select;
use App\Models\BuyGiftCardTransaction;

use App\Models\RewardWalletTransaction;
use App\Http\Controllers\CustomNotificationController;
use App\Http\Controllers\UserCryptoWalletTransactionController;

class HomeController extends Controller
{

    public function index()
    {
        $user = auth('api')->user();

        // wallet
        $wallet = $user->Wallet()->select('balance')->first();
        $rewardWallet = RewardWallet::where("user_id", $user->id)->select('balance')->first();

        $serData = Service::select('id', 'title', 'icon', 'description', 'service_type', 'transaction_icon', 'status')->get();

        $promotions = BannerPromotion::where("status", 1)->select('id', 'title', 'banner_url', 'promotion_url')->get();

        $services = [];
        foreach ($serData as $service) {

            $item['id'] = $service->id;
            $item['title'] = $service->title;
            $item['icon'] =  env('APP_URL') . $service->icon;
            $item['description'] = $service->description;
            $item['service_type'] = $service->service_type;
            $item['status'] = $service->status;
            $item['transaction_icon'] = env('APP_URL') . $service->transaction_icon;

            $services[] = $item;
        };

        $card = MapleVirtualCard::where("user_id", $user->id)->where("status", "ACTIVE")->first();
        $usd_bal = $card ? ($card->balance / 100) : 0;

        $data = [
            'wallet_balance' =>  number_format((float) $wallet->balance, 2),
            'usd_balance' =>  number_format((float) $usd_bal, 2),
            'reward_balance' =>  number_format((float) $rewardWallet->balance, 2),
            'services' => $services,
            'promotions' =>  $promotions,
            'devices' =>  UserDevice::where('user_id', $user->id)->select(
                'id',
                'token_id',
                'user_id',
                'user_agent',
                'device_id',
                'ip',
                "name",
                "os",
                "location",
                "isp",
                "created_at",
                "updated_at"
            )->get(),
        ];
        return response($data, 200);
    }

    public function adminIndex()
    {
        $user = auth('api')->user();

        $promotions = BannerPromotion::where("status", 1)->select('id', 'title', 'banner_url', 'promotion_url')->get();

        // get 5 gift cards
        $giftCards = GiftCard::select('id', 'title', 'image', 'brand_logo', 'service_id', 'status')->limit(5)->get();

        $giftCardsData = [];
        foreach ($giftCards as $giftCard) {
            $item['id'] = $giftCard->id;
            $item['title'] = $giftCard->title;
            $item['image'] = env('APP_URL') . $giftCard->image;
            $item['brand_logo'] = env('APP_URL') . $giftCard->brand_logo;
            $item['service_id'] = $giftCard->service_id;
            $giftCardsData[] = $item;
        }

        // wallet
        // $wallet = $user->Wallet()->select('balance')->first();

        $serData = Service::select('id', 'title', 'icon', 'description', 'service_type', 'transaction_icon')->get();
        $services = [];
        foreach ($serData as $service) {

            $item['id'] = $service->id;
            $item['title'] = $service->title;
            $item['icon'] =  env('APP_URL') . $service->icon;
            $item['description'] = $service->description;
            $item['service_type'] = $service->service_type;
            $item['transaction_icon'] = env('APP_URL') . $service->transaction_icon;

            $services[] = $item;
        };

        $transData = Transaction::where('status', 0)->where('service_id', '<', 3)->get()->sortByDesc('created_at');

        $transactions = [];
        foreach ($transData as $transaction) {
            $ser = Service::where('id', $transaction->service_id)->first();
            if (!$transaction->icon) {
                $icon = $ser->transaction_icon;
            } else {
                $icon = $transaction->icon;
            }

            if (!$transaction->icon) {
                $icon = $ser->transaction_icon;
            } else {
                $icon = $transaction->icon;
            }
            if ($transaction->service_id == 1) {
                // get giftcard trx
                $giftTrx = GiftCardTransaction::where('transaction_id', $transaction->id)->first();
                // get giftcard country
                $giftCardCountry = GiftCardsCountry::where('id', $giftTrx->gift_card_country_id)->first();
                // get country
                $giftCountry = country::where('id', $giftCardCountry->country_id)->first();
                $iso = $giftCountry->iso;
            } else if ($transaction->service_id == 2) {
                $iso = 'US';
            } else {
                $iso = 'NG';
            }

            $transItem['id'] = $transaction->id;
            $transItem['user_id'] = $transaction->user_id;
            $transItem['icon'] =  env('APP_URL') . $icon;
            $transItem['service_id'] = $transaction->service_id;
            $transItem['usd_amount'] = $transaction->usd_amount;
            $transItem['usd_rate'] = $transaction->usd_rate;
            $transItem['ngn_amount'] = $transaction->ngn_amount;
            $transItem['transaction_ref'] = $transaction->transaction_ref;
            $transItem['status'] = $transaction->status;
            $transItem['title'] =  $transaction->title;
            $transItem['iso'] =  $iso;
            $transItem['created_at'] =  $transaction->created_at;

            $transactions[] = $transItem;

            // /// create swithc for the status
            // switch ($transaction->status) {
            //     case 0:
            //         $transItem['status'] = 'Pending';
            //         break;
            //     case 1:
            //         $transItem['status'] = 'Success';
            //         break;
            //     case 2:
            //         $transItem['status'] = 'Failed';
            //         break;
            //     case 3:
            //         $transItem['status'] = 'Cancelled';
            //         break;
            //     default:
            //         $transItem['status'] = 'Pending';
            //         break;
            // }

            // $transactions[] = $transItem;
        }

        $data = [
            'promotions' => $promotions,
            // 'crypto_rates' =>  $crypto_rates,
            'gift_cards' => $giftCardsData,
            'transaction' => array_slice($transactions, 0, 5),
        ];
        return response($data, 200);
    }

    public function adminTrans()
    {

        $giftData = GiftCardTransaction::where('status', 0)->get()->sortByDesc('created_at');
        $walData = WalletTransactions::where('status', 0)->get()->sortByDesc('created_at');


        // $giftData = Transaction::where('service_id', 1)->where('status', 0)->get();
        // $bitData = Transaction::where('service_id', 2)->where('status', 0)->get();
        // // $utiData = BillTransaction::get();
        // $withData = WalletTransactions::where('status', 0)->get();
        $giftTrans = [];
        foreach ($giftData as $giftTran) {

            $giftCard = GiftCard::where('id', $giftTran->gift_card_id)->first();
            $giftcountry = GiftCardsCountry::where("id", $giftTran->gift_card_country_id)->first();
            $country = Country::where("id", $giftcountry->country_id)->first();
            $generalCountry = GeneralCountry::where("countryCode", strtoupper($country->iso))->first();
            $currency = Currencies::where("code", $generalCountry->currencyCode)->first();



            $giftItem['id'] = $giftTran->id;
            $giftItem['title'] = $currency->symbol . $giftTran->card_value . ($giftTran->qty == 1 ? "" : (" x" . ($giftTran->status == 0 ? $giftTran->qty : $giftTran->approved_qty)));
            $giftItem['type'] = "giftcard";
            $giftItem['user_id'] = $giftTran->user_id;
            $giftItem['icon'] =  env('APP_URL') . $giftCard->brand_logo;
            $giftItem['service_id'] = $giftTran->service_id;
            $giftItem['usd_amount'] = $giftTran->card_value;
            $giftItem['usd_rate'] = $giftTran->rate;
            $giftItem['ngn_amount'] = number_format((float) $giftTran->card_value * $giftTran->rate * ($giftTran->status == 0 ? ($giftTran->qty ?? 1) : $giftTran->approved_qty), 2);
            $giftItem['transaction_ref'] = $giftTran->transaction_ref;
            $giftItem['status'] = $giftTran->status;
            $giftItem['created_at'] = $giftTran->created_at;
            $giftItem['iso'] = $giftcountry->iso;

            $giftTrans[] = $giftItem;
        }



        $crypto_gether = array_merge(
            $bitTrans ?? [],
            $cryptoTrans ?? [],
            $buyCryptoTrans ?? []
        );

        if (count($crypto_gether) < 1) {
            $crypto_gether = [];
        } else {
            // sort by latest created at
            usort($crypto_gether, function ($a, $b) {
                return $b['created_at'] <=> $a['created_at'];
            });
        }

        $withTrans = [];
        foreach ($walData as $withTran) {
            // $bwithser = Service::where('id', $bitTran->service_id)->first();
            $withItem['id'] = $withTran->id;
            $withItem['user_id'] = $withTran->user_id;
            $withItem['title'] =  $withTran->type == "transfer" ? "Funds Transfer" : "Withdrawal";
            $withItem['type'] = "wallet";
            $withItem['icon'] =  env('APP_URL') . "/images/services/withdraw.png";
            $withItem['service_id'] = 0;
            $withItem['usd_amount'] = $withTran->amount;
            $withItem['usd_rate'] = 1;
            $withItem['ngn_amount'] = $withTran->amount;
            $wthItem['transaction_ref'] = $withTran->id;
            $withItem['bank_name'] = $withTran->bank_name;
            $withItem['account_number'] = $withTran->account_number;
            $withItem['account_name'] = $withTran->account_name;
            $withItem['status'] = $withTran->status;
            $withItem['created_at'] = $withTran->created_at;
            $withItem['iso'] = 'NG';


            $withTrans[] = $withItem;
        }


        $data = [
            'gift_cards' => $giftTrans,
            'withdrawals' => $withTrans,
            'buy_crypto' => $buyCryptoTrans ?? [],
            'buy_gether' => $crypto_gether

        ];
        return response($data, 200);
    }

    public function allTrans()
    {
        $user = auth('api')->user();

        $giftData = GiftCardTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $utiData = BillTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $walData = WalletTransactions::where('user_id', $user->id)->get()->sortByDesc('created_at');

        $betData = BettingTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $trfData = FundTransferRequest::where('user_id', $user->id)->orWhere('account_no', $user->email)->orWhere('account_no', $user->username)->get()->sortByDesc('created_at');
        $trf2Data = SpaceTransfer::where('account_no', $user->email)->orWhere('account_no', $user->username)->get()->sortByDesc('created_at');

        $buyData = BuyGiftCardTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $fundData = FundTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');


        foreach ($fundData as $walletTransaction) {
            switch ($walletTransaction->status) {
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

            $transaction = $walletTransaction;
            // $transaction->status = $status;


            $fundTrans[] = [
                'id' => $walletTransaction->id,
                'title' => "Wallet Funding",
                'type' => 'fund',
                'sub_type_id' => 0,
                'icon' => env('APP_URL') . "/images/services/fund.png",
                'amount' => number_format((float) $walletTransaction->amount, 2),
                'status' => $status,
                'created_at' => $walletTransaction->created_at,
                'updated_at' => $walletTransaction->created_at,
                'trx' => $transaction,
            ];
        }

        foreach ($utiData as $billTransaction) {
            $utility = Utility::where('id', $billTransaction->utility_id)->first();
            switch ($billTransaction->status) {
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

            // fill up trx
            $transaction = $billTransaction;
            $transaction->service_icon = env('APP_URL') . $billTransaction->service_icon;
            $transaction->service_id = $billTransaction->utility_id;
            $transaction->status = $status;
            $transaction->utility_name = $utility->name;
            $transaction->utility_prefix = $utility->prefix;


            $billTrans[] = [
                'id' => $billTransaction->id,
                'title' => $billTransaction->type,
                'type' => 'bill',
                'sub_type_id' => $billTransaction->utility_id,
                'icon' => $billTransaction->service_icon,
                'amount' => $billTransaction->amount,
                'status' => $status,
                'created_at' => $billTransaction->created_at,
                'updated_at' => $billTransaction->created_at,
                'trx' => $transaction,
            ];
        }

        foreach ($giftData as $giftTransaction) {
            switch ($giftTransaction->status) {
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


            $giftCard = GiftCard::where('id', $giftTransaction->gift_card_id)->first();
            $giftcountry = GiftCardsCountry::where("id", $giftTransaction->gift_card_country_id)->first();
            $country = Country::where("id", $giftcountry->country_id)->first();
            $cardRange = CardRange::withTrashed()->where('id', $giftTransaction->card_range_id)->first();
            $generalCountry = GeneralCountry::where("countryCode", strtoupper($country->iso))->first();
            $currency = Currencies::where("code", $generalCountry->currencyCode)->first();
            $category = GiftCardCategory::withTrashed()->where('id', $giftTransaction->gift_card_category_id)->first();

            // fill up trx
            $transaction = $giftTransaction;
            $transaction->status = $status;
            $transaction->icon = env('APP_URL') . $giftCard->brand_logo;
            $transaction->gift_card_image = env('APP_URL') . $giftCard->image;
            $transaction->service_id = 1;
            $transaction->usd_rate = $giftTransaction->rate;
            $transaction->ngn_amount = $giftTransaction->rate * $giftTransaction->card_value;
            $transaction->transaction_ref = $giftTransaction->id;
            $transaction->giftcard_type = $giftCard->title;
            if ($giftTransaction->second_proof) {
                $transaction->second_proof = env('APP_URL') . $giftTransaction->second_proof;
            }
            if ($giftTransaction->third_proof) {
                $transaction->third_proof = env('APP_URL') . $giftTransaction->third_proof;
            }
            $transaction->proof = (str_contains($giftTransaction->proof, "https://") ? "" : env('APP_URL')) . $giftTransaction->proof;
            $transaction->category = $category->title;
            $transaction->currency = $currency;
            $transaction->range = [
                'max' => $cardRange->max,
                'min' => $cardRange->min,
                'rate' => $giftTransaction->rate,
            ];
            $transaction->country = $country->name;
            $transaction->iso = $country->iso;
            $transaction->proofs = $giftTransaction->proofs ? json_decode($giftTransaction->proofs) : [];

            if ($transaction->promo_code) {
                $promo = PromoCode::where("code", strtolower($transaction->promo_code))->where("deleted_at", null)->first();
                if ($promo) {
                    $transaction->promo_gain = strval($promo->gain);
                }
            }


            $giftTrans[] = [
                'id' => $giftTransaction->id,
                'title' => $giftCard->title . " - " . $currency->symbol . $giftTransaction->card_value . ($giftTransaction->qty == 1 ? "" : (" x" . ($giftTransaction->status == 0 ? $giftTransaction->qty : $giftTransaction->approved_qty))),
                'type' => 'giftcard',
                'sub_type_id' => $giftCard->id,
                'icon' => env('APP_URL') . $giftCard->brand_logo,
                'amount' => number_format((float) $giftTransaction->rate * $giftTransaction->card_value * ($giftTransaction->status == 0 ? $giftTransaction->qty : $giftTransaction->approved_qty), 2),
                'status' => $status,
                'created_at' => $giftTransaction->created_at,
                'updated_at' => $giftTransaction->created_at,
                'trx' => $transaction,
            ];
        }

        foreach ($buyData as $giftTransaction) {

            $transaction = $giftTransaction;
            $buyTrans[] = [
                'id' => $giftTransaction->id,
                'title' => $giftTransaction->product_name . " - " . $giftTransaction->product_currency_code . $giftTransaction->unit_price . " x" . $giftTransaction->quantity,
                'type' => 'buy_giftcard',
                'sub_type_id' => $giftTransaction->productId,
                'icon' => $giftTransaction->card_logo,
                'amount' => number_format($giftTransaction->amount, 2),
                'status' => $giftTransaction->status,
                'created_at' => $giftTransaction->created_at,
                'updated_at' => $giftTransaction->created_at,
                'trx' => $transaction,
            ];
        }

        foreach ($walData as $walletTransaction) {
            switch ($walletTransaction->status) {
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

            // fill up trx
            $transaction = $walletTransaction;
            $transaction->status = $status;

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
            $transaction->bank = $bank;


            $wallTrans[] = [
                'id' => $walletTransaction->id,
                'title' => $walletTransaction->type == "transfer" ? "Funds Transfer" : "Withdrawal",
                'type' => 'wallet',
                'sub_type_id' => 0,
                'icon' => env('APP_URL') . "/images/services/withdraw.png",
                'amount' => number_format((float) $walletTransaction->amount, 2),
                'status' => $status,
                'created_at' => $walletTransaction->created_at,
                'updated_at' => $walletTransaction->created_at,
                'trx' => $transaction,
            ];
        }



        foreach ($betData as $betTransaction) {
            switch ($betTransaction->status) {
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


            // fill up trx
            $transaction = $betTransaction;
            $transaction->status = $status;
            $betTrans[] = [
                'id' => $betTransaction->id,
                'title' => $betTransaction->product,
                'type' => 'bet_bill-' . $betTransaction->product,
                'sub_type_id' => $betTransaction->id,
                'icon' => "https://res.cloudinary.com/db3c1repq/image/upload/v1713497804/_1baa1896-2b36-44f9-8cd4-b71dcfc2efae_ghvd6j.jpg",
                'amount' => number_format($betTransaction->amount, 2),
                'status' => $status,
                'created_at' => $betTransaction->created_at,
                'updated_at' => $betTransaction->updated_at,
                'trx' => $transaction,
            ];
        }

        foreach ($trfData as $trfTransaction) {
            switch ($trfTransaction->status) {
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

            // fill up trx
            $transaction = $trfTransaction;
            $transaction->status = $status;

            $name = str_replace(",", "", $trfTransaction->account_name);
            $name = str_replace("  ", " ", $trfTransaction->account_name);
            $name = explode(' ', $name);
            $firstname = $name[0];
            $lastname = $name[1] ?? $name[0];

            $trx_user = User::find($trfTransaction->user_id);

            $transaction->sender = $trx_user ? ($trx_user->first_name . " " . $trx_user->last_name[0] . ".") : "SpaceTrade User";

            $wallTrans[] = [
                'id' => $trfTransaction->id,
                'title' => $trfTransaction->account_no == $user->email || $trfTransaction->account_no == $user->username ? "From " . ($trx_user ? $trx_user->first_name : "a SpaceTrade User") : ($lastname ?? "" . ' ' . $firstname[0] ?? ""),
                'type' => $trfTransaction->type . ($trfTransaction->account_no == $user->email || $trfTransaction->account_no == $user->username ? "_in" : "_transfer"),
                'sub_type_id' => 0,
                'icon' => env('APP_URL') . "/images/services/transfer" . ($trfTransaction->account_no == $user->email || $trfTransaction->account_no == $user->username ? "_in" : "") . ".png",
                'amount' => number_format((float) $trfTransaction->amount, 2),
                'status' => $status,
                'created_at' => $trfTransaction->created_at,
                'updated_at' => $trfTransaction->created_at,
                'trx' => $transaction,
            ];
        }

        foreach ($trf2Data as $trfTransaction) {
            switch ($trfTransaction->status) {
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

            // fill up trx
            $transaction = $trfTransaction;
            $transaction->status = $status;

            $name = str_replace(",", "", $trfTransaction->account_name);
            $name = str_replace("  ", " ", $trfTransaction->account_name);
            $name = explode(' ', $name);
            $firstname = $name[0];
            $lastname = $name[1] ?? $name[0];

            $trx_user = User::find($trfTransaction->user_id);

            $wallTrans[] = [
                'id' => $trfTransaction->id,
                'title' => "SpaceTrade Bonus",
                'type' => "space_bonus",
                'sub_type_id' => 0,
                'icon' => env('APP_URL') . "/images/services/space_bonus.png",
                'amount' => number_format((float) $trfTransaction->amount, 2),
                'status' => $status,
                'created_at' => $trfTransaction->created_at,
                'updated_at' => $trfTransaction->created_at,
                'trx' => $transaction,
            ];
        }

        // merge the three arrays
        $transactions = array_merge(
            $wallTrans ?? [],
            $giftTrans ?? [],
            $fundTrans ?? [],
            $billTrans ?? [],
            $betTrans ?? [],
            $buyTrans ?? []
        );

        if (count($transactions) < 1) {
            return response([], 200);
        }

        // sort by latest created at
        usort($transactions, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });
        return response($transactions, 200);
    }

    public function allUserTrans($id)
    {
        $user = User::where("id", $id)->first();

        // Define the start and end of July 10
        $startDate = Carbon::create(2024, 7, 10, 8, 45, 0);
        $endDate = Carbon::now();


        $giftData = GiftCardTransaction::where('user_id', $id)->whereBetween('updated_at', [$startDate, $endDate])->get()->sortByDesc('created_at');
        $utiData = BillTransaction::where('user_id', $id)->get()->sortByDesc('created_at');
        $walData = WalletTransactions::where('user_id', $id)->get()->sortByDesc('created_at');
        $refData = RewardWalletTransaction::where('user_id', $id)->whereBetween('updated_at', [$startDate, $endDate])->get()->sortByDesc('created_at');
        $trf2Data = SpaceTransfer::where('account_no', $user->email)->orWhere('account_no', $user->username)->get()->sortByDesc('created_at');
        $betData = BettingTransaction::where('user_id', $id)->get()->sortByDesc('created_at');
        $trfData = FundTransferRequest::where(function ($query) use ($id, $user) {
            $query->where('user_id', $id)
            ->orWhere('recepient_id', $id)
            ->orWhere('account_no', $user->email)
                ->orWhere('account_no', $user->username);
        })
            ->orderBy('created_at', 'desc')
            ->get();

        $buyData = BuyGiftCardTransaction::where('user_id', $id)->get()->sortByDesc('created_at');


        $billTrans = [];
        foreach ($utiData as $billTransaction) {
            $utility = Utility::where('id', $billTransaction->utility_id)->first();
            switch ($billTransaction->status) {
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


            // fill up trx
            $transaction = $billTransaction;
            $transaction->service_icon = env('APP_URL') . $billTransaction->service_icon;
            $transaction->status = $status;
            $transaction->utility_name = $utility->name;
            $transaction->utility_prefix = $utility->prefix;

            $billTrans[] = [
                'id' => $billTransaction->id,
                'title' => $billTransaction->type,
                'type' => 'bill',
                'sub_type_id' => $billTransaction->utility_id,
                'icon' => $transaction->service_icon,
                'amount' => $billTransaction->amount,
                'status' => $status,
                'created_at' => $billTransaction->created_at,
                'updated_at' => $billTransaction->created_at,
                'trx' => $transaction,
            ];
        }



        $giftTrans = [];
        foreach ($giftData as $giftTransaction) {
            switch ($giftTransaction->status) {
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
                case 4:
                    $status = 'Loading';
                    break;
                default:
                    $status = 'Pending';
                    break;
            }

            $giftCard = GiftCard::where('id', $giftTransaction->gift_card_id)->first();
            $giftcountry = GiftCardsCountry::where("id", $giftTransaction->gift_card_country_id)->first();
            $country = Country::where("id", $giftcountry->country_id)->first();
            $generalCountry = GeneralCountry::where("countryCode", strtoupper($country->iso))->first();
            $currency = Currencies::where("code", $generalCountry->currencyCode)->first();


            $giftTrans[] = [
                'id' => $giftTransaction->id,
                'title' => $giftCard->title . " - " . $currency->symbol . $giftTransaction->card_value . ($giftTransaction->qty == 1 ? "" : (" x" . ($status == 'Completed' ? $giftTransaction->approved_qty : $giftTransaction->qty))),
                'type' => 'giftcard',
                'sub_type_id' => $giftCard->id,
                'icon' => env('APP_URL') . $giftCard->brand_logo,
                'amount' => number_format((float) $giftTransaction->rate * $giftTransaction->card_value * ($status == 'Completed' ? ($giftTransaction->approved_qty ?? 1) : ($giftTransaction->qty ?? 1)), 2),
                'status' => $status,
                'created_at' => $giftTransaction->created_at,
                'updated_at' => $giftTransaction->created_at,
            ];
        }

        $wallTrans = [];
        foreach ($walData as $walletTransaction) {
            switch ($walletTransaction->status) {
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

            // fill up trx
            $transaction = $walletTransaction;
            $transaction->status = $status;

            $wallTrans[] = [
                'id' => $walletTransaction->id,
                'title' => "Withdrawal",
                'type' => 'wallet',
                'sub_type_id' => 0,
                'icon' => env('APP_URL') . "/images/services/withdraw.png",
                'amount' => number_format((float) $walletTransaction->amount, 2),
                'status' => $status,
                'created_at' => $walletTransaction->created_at,
                'updated_at' => $walletTransaction->created_at,
                'trx' => $transaction,
                'charge' => $transaction->charge,
            ];
        }

        foreach ($refData as $rewardTransaction) {
            switch ($rewardTransaction->status) {
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
                    $status = 'Awaiting Payment';
                    break;
                default:
                    $status = 'Pending';
                    break;
            }

            $title = "Reward";
            $refUser = User::where("id", $rewardTransaction->referred_user_id)->first();
            if ($rewardTransaction->referred_user_id != 0 && $refUser) {
                $title =  $refUser->first_name . "'s Referral";
            } else {
                $title = "Reward Earned";
            }

            $wallTrans[] = [
                'id' => $rewardTransaction->id,
                'title' => $title,
                'type' => 'reward',
                'sub_type_id' => 0,
                'icon' => env('APP_URL') . "/images/services/reward.png",
                'amount' => number_format((float) $rewardTransaction->amount, 2),
                'status' => $status,
                'created_at' => $rewardTransaction->created_at,
                'updated_at' => $rewardTransaction->updated_at,
            ];
        }

        foreach ($betData as $betTransaction) {
            switch ($betTransaction->status) {
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


            // fill up trx
            $transaction = $betTransaction;
            $transaction->status = $status;
            $betTrans[] = [
                'id' => $betTransaction->id,
                'title' => $betTransaction->product,
                'type' => 'bet_bill-' . $betTransaction->product,
                'sub_type_id' => $betTransaction->id,
                'icon' => "https://res.cloudinary.com/db3c1repq/image/upload/v1713497804/_1baa1896-2b36-44f9-8cd4-b71dcfc2efae_ghvd6j.jpg",
                'amount' => number_format($betTransaction->amount, 2),
                'status' => $status,
                'created_at' => $betTransaction->created_at,
                'updated_at' => $betTransaction->updated_at,
                'charge' => $transaction->charge,
                'trx' => $transaction,
            ];
        }

        foreach ($trfData as $trfTransaction) {
            switch ($trfTransaction->status) {
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

            // fill up trx
            $transaction = $trfTransaction;
            $transaction->status = $status;

            $name = str_replace(",", "", $trfTransaction->account_name);
            $name = str_replace("  ", " ", $trfTransaction->account_name);
            $name = explode(' ', $name);
            $firstname = $name[0];
            $lastname = $name[1] ?? $name[0];

            $trx_user = User::find($trfTransaction->user_id);

            $wallTrans[] = [
                'id' => $trfTransaction->id,
                'title' => $trfTransaction->account_no == $user->email || $trfTransaction->account_no == $user->username ? "From " . ($trx_user ? $trx_user->first_name : "a SpaceTrade User") : ($lastname ?? "" . ' ' . $firstname[0] ?? ""),
                'type' => $trfTransaction->type . ($trfTransaction->account_no == $user->email || $trfTransaction->account_no == $user->username ? "_in" : "_transfer"),
                'sub_type_id' => 0,
                'icon' => env('APP_URL') . "/images/services/transfer" . ($trfTransaction->account_no == $user->email || $trfTransaction->account_no == $user->username ? "_in" : "") . ".png",
                'amount' => number_format((float) $trfTransaction->amount, 2),
                'status' => $status,
                'created_at' => $trfTransaction->created_at,
                'updated_at' => $trfTransaction->created_at,
                'trx' => $transaction,
                'charge' => $transaction->charge,
            ];
        }

        foreach ($buyData as $giftTransaction) {

            $transaction = $giftTransaction;
            $buyTrans[] = [
                'id' => $giftTransaction->id,
                'title' => $giftTransaction->product_name . " - " . $giftTransaction->product_currency_code . $giftTransaction->unit_price . " x" . $giftTransaction->quantity,
                'type' => 'buy_giftcard',
                'sub_type_id' => $giftTransaction->productId,
                'icon' => 'https://res.cloudinary.com/db3c1repq/image/upload/v1717585205/buy_gift_saz9ii.png',
                'amount' => number_format($giftTransaction->amount, 2),
                'status' => $giftTransaction->status,
                'created_at' => $giftTransaction->created_at,
                'updated_at' => $giftTransaction->created_at,
                'trx' => $transaction,
            ];
        }

        foreach ($trf2Data as $trfTransaction) {
            switch ($trfTransaction->status) {
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

            // fill up trx
            $transaction = $trfTransaction;
            $transaction->status = $status;

            $name = str_replace(",", "", $trfTransaction->account_name);
            $name = str_replace("  ", " ", $trfTransaction->account_name);
            $name = explode(' ', $name);
            $firstname = $name[0];
            $lastname = $name[1] ?? $name[0];

            $trx_user = User::find($trfTransaction->user_id);

            $wallTrans[] = [
                'id' => $trfTransaction->id,
                'title' => "SpaceTrade Bonus",
                'type' => "space_bonus",
                'sub_type_id' => 0,
                'icon' => env('APP_URL') . "/images/services/space_bonus.png",
                'amount' => number_format((float) $trfTransaction->amount, 2),
                'status' => $status,
                'created_at' => $trfTransaction->created_at,
                'updated_at' => $trfTransaction->created_at,
                'trx' => $transaction,
            ];
        }



        // merge the three arrays
        $transactions = array_merge(
            $wallTrans,
            $giftTrans,
            $billTrans ?? [],
            $betTrans ?? [],
            $buyTrans ?? []
        );
        if (count($transactions) < 1) {
            return response(
                [
                    'message' => "Transactions fetched successfulluy",
                    'transactions' =>  [],

                ],
                200
            );
        }

        // sort by latest created at
        usort($transactions, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });

        $data = [
            'message' => "Transactions fetched successfulluy",
            'transactions' =>  $transactions,

        ];
        return response($data, 200);
    }


    public function profile()
    {
        $user = auth('api')->user();
        // if user has photo then return it else return default
        if ($user->photo) {
            $user->photo = env('APP_URL') . $user->photo;
        }
        return response($user, 200);
    }

    public function allUsers()
    {
        $users = User::select('id', 'first_name', 'last_name', 'email', 'phone', 'role', 'created_at', 'photo', 'username', 'referrer', 'status')->get();

        foreach ($users as $user) {
            $user_wallet = Wallet::where("user_id", $user->id)->first();
            $user->balance = $user_wallet->balance;
            $user->photo = env('APP_URL') . $user->photo;
            if ($user->referrer && $user->referrer !=  null) {
                $user->referrer = User::where("username", $user->referrer)->first()->name;
            }
        }


        // return response with message and data
        return response($users, 200);
    }

    public function testFcm()
    {
        try {
            FCMService::send(
                "c_xs-5CwQx28aeTmCQR5uk:APA91bGvM2miMR4sF32HX1tygremKnoV8pIMXa33hFI4n8kSVVQzVSwoCKBWBFk7Z1QtW_CZow9KPL34x1oKI0awnD7yhiJCSbvRAgB-fFxz7Ic216bUPPNIzwIjN6763o-RTWk69J3u",
                [
                    'title' => '🎉 GiftCard Transaction Successfull',
                    'body' => "Your GiftCard transaction with the reference # was successful. Pleae check app for more details.",
                ]
            );
        } catch (Exception $e) {
            return response($e, 422);
        }

        return response("done", 200);
    }

    public function getGiftCardLeaderBoard2()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $today = Carbon::now();
        $yesterday = Carbon::now()->subDay();

        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $authUser = auth('api')->user(); // Get the authenticated user

        // Fetch user information for all unique user IDs in the current, previous, and last month's leaderboards once
        $allUserIds = GiftCardTransaction::where('status', 1)
            ->whereBetween('created_at', [$startOfMonth, $today])
            ->orWhere(function ($query) use ($startOfMonth, $yesterday, $lastMonthStart, $lastMonthEnd) {
                $query->whereBetween('created_at', [$startOfMonth, $yesterday])
                    ->orWhereBetween('created_at', [$lastMonthStart, $lastMonthEnd]);
            })
            ->pluck('user_id')
            ->unique();

        // Fetch all users in one query
        $allUsers = User::whereIn('id', $allUserIds)->select('id', 'username', 'first_name')->get()->keyBy('id');

        // Fetch current leaderboard
        $currentLeaderboard = GiftCardTransaction::where('status', 1)
            ->whereBetween('created_at', [$startOfMonth, $today])
            ->select('user_id', DB::raw('SUM((card_value * rate * approved_qty) / 1000) as points'))
            ->groupBy('user_id')
            ->orderByDesc('points')
            ->get();

        // Assign user info and rank to current leaderboard
        $currentLeaderboard->each(function ($trader, $index) use ($allUsers) {
            $trader->user = $allUsers[$trader->user_id] ?? User::find(1); // Fallback to User with ID 1
            $trader->rank = $index + 1;
        });

        // Fetch previous leaderboard (from this month till yesterday)
        $previousLeaderboard = GiftCardTransaction::where('status', 1)
            ->whereBetween('created_at', [$startOfMonth, $yesterday])
            ->select('user_id', DB::raw('SUM((card_value * rate * approved_qty) / 1000) as points'))
            ->groupBy('user_id')
            ->orderByDesc('points')
            ->get();

        // Assign user info and rank to previous leaderboard
        $previousLeaderboard->each(function ($trader, $index) use ($allUsers) {
            $trader->user = $allUsers[$trader->user_id] ?? User::find(1); // Fallback to User with ID 1
            $trader->rank = $index + 1;
        });

        // Fetch last month's leaderboard
        $lastMonthLeaderboard = GiftCardTransaction::where('status', 1)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->select('user_id', DB::raw('SUM((card_value * rate * approved_qty) / 1000) as points'))
            ->groupBy('user_id')
            ->orderByDesc('points')
            ->get();

        // Assign user info and rank to last month's leaderboard
        $lastMonthLeaderboard->each(function ($trader, $index) use ($allUsers) {
            $trader->user = $allUsers[$trader->user_id] ?? User::find(1); // Fallback to User with ID 1
            $trader->rank = $index + 1;
        });

        // Total number of ranked users yesterday and last month
        $totalPreviousRankedUsers = $previousLeaderboard->count();
        $totalLastMonthRankedUsers = $lastMonthLeaderboard->count();

        // Calculate rank changes for the current leaderboard
        $currentLeaderboard->each(function ($currentPayer) use ($previousLeaderboard, $totalPreviousRankedUsers) {
            $previousPayer = $previousLeaderboard->firstWhere('user_id', $currentPayer->user_id);

            if ($previousPayer) {
                // Calculate rank difference if found in previous leaderboard
                $currentPayer->rank_change = $previousPayer->rank - $currentPayer->rank;
            } else {
                // If not found in previous leaderboard, it's a new entry
                $currentPayer->rank_change = $totalPreviousRankedUsers - $currentPayer->rank + 1;
            }
        });

        // Fetch authenticated user's current rank and points
        $authUserCurrent = $currentLeaderboard->firstWhere('user_id', $authUser->id);
        $authUserPrevious = $previousLeaderboard->firstWhere('user_id', $authUser->id);
        $authUserLastMonth = $lastMonthLeaderboard->firstWhere('user_id', $authUser->id);

        // Calculate rank change for authenticated user
        $authUserRankChange = $authUserCurrent && $authUserPrevious
            ? $authUserPrevious->rank - $authUserCurrent->rank
            : ($authUserCurrent ? $totalPreviousRankedUsers - $authUserCurrent->rank + 1 : null);

        $authUserData = $authUserCurrent ? [
            'user' => $allUsers[$authUser->id] ?? User::find(1),
            'rank' => $authUserCurrent->rank ?? 0,
            'points' => $authUserCurrent->points ?? 0,
            'rank_change' => $authUserRankChange ?? 0
        ] : null;

        // Prepare last month's leaderboard for top 10
        $lastMonthTop10 = $lastMonthLeaderboard->take(10);

        return [
            'leaderboard' => $currentLeaderboard->take(10),
            'auth_user' => $authUserData,
            'last_month_leaderboard' => $lastMonthTop10
        ];
    }

    public function getGiftCardLeaderBoard()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $today = Carbon::now();
        $yesterday = Carbon::now()->subDay();

        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Define start and end for the current week and last week
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now(); // Today
        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek();
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek();

        $authUser = auth('api')->user(); // Get the authenticated user

        // Fetch user information for all unique user IDs combined
        $allUserIds = GiftCardTransaction::where('status', 1)
            ->where('user_id', '!=', 0)
            ->whereBetween('created_at', [Carbon::now()->subMonths(6)->startOfMonth(), $today])
            ->orwhereBetween('created_at', [$startOfMonth, $today])
            ->orWhereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->orWhereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->orWhereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
            ->pluck('user_id')
            ->unique();

        // Fetch all users in one query
        $allUsers = User::whereIn('id', $allUserIds)->select('id', 'username', 'first_name')->get()->keyBy('id');

        // Helper function to fetch leaderboard and assign user info with limit
        $getLeaderboard = function ($startDate, $endDate) use ($allUsers) {
            return GiftCardTransaction::where('status', 1)
                ->where('user_id', '!=', 0)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('user_id', DB::raw('SUM((card_value * rate * approved_qty) / 1500) as points'))
                ->groupBy('user_id')
                ->orderByDesc('points')
                // ->limit(10) // Limit to top 10
                ->get()
                ->map(function ($trader, $index) use ($allUsers) {
                    $trader->user = $allUsers[$trader->user_id] ?? User::find(1); // Fallback to User with ID 1
                    $trader->rank = $index + 1; // Adding rank here
                    return $trader;
                });
        };

        // Fetch current monthly leaderboard
        $monthlyLeaderboard = $getLeaderboard($startOfMonth, $today);

        // Fetch last month's leaderboard
        $lastMonthLeaderboard = $getLeaderboard($lastMonthStart, $lastMonthEnd);

        // Fetch current weekly leaderboard
        $weeklyLeaderboard = $getLeaderboard($startOfWeek, $endOfWeek);

        // Fetch last week's leaderboard
        $lastWeekLeaderboard = $getLeaderboard($lastWeekStart, $lastWeekEnd);

        // Fetch last six months' monthly leaderboards
        $lastSixMonthsLeaderboards = [];
        for ($i = 1; $i <= 6; $i++) {
            $startOfLastMonth = Carbon::now()->subMonths($i)->startOfMonth();
            $endOfLastMonth = Carbon::now()->subMonths($i)->endOfMonth();
            $leaderboardData = $getLeaderboard($startOfLastMonth, $endOfLastMonth);

            // Add month and year information
            $monthYear = $startOfLastMonth->format('M Y'); // e.g., "September 2024"

            $lastSixMonthsLeaderboards[] = [
                'month' => $monthYear,
                'data' => $leaderboardData
            ];
        }

        // Fetch authenticated user's current rank and points in the monthly and weekly leaderboard
        $authUserCurrentMonthly = $monthlyLeaderboard->firstWhere('user_id', $authUser->id);
        $authUserCurrentWeekly = $weeklyLeaderboard->firstWhere('user_id', $authUser->id);

        // Include authenticated user's data
        $authUserMonthlyData = $authUserCurrentMonthly ?? [
            'user' => User::where("id", $authUser->id)->select('id', 'username', 'first_name')->first(),
            'rank' => 0,
            'points' =>  0,
        ];

        $authUserWeeklyData = $authUserCurrentWeekly ?? [
            'user' => User::where("id", $authUser->id)->select('id', 'username', 'first_name')->first(),
            'rank' => 0,
            'points' =>  0,
        ];

        return [
            'current_month_leaderboard' => $monthlyLeaderboard, // Current month's leaderboard with top 10
            'last_month_leaderboard' => $lastMonthLeaderboard, // Last month's leaderboard with top 10
            'current_week_leaderboard' => $weeklyLeaderboard, // Current week's leaderboard with top 10
            'last_week_leaderboard' => $lastWeekLeaderboard, // Last week's leaderboard with top 10
            'last_six_months_leaderboards' => $lastSixMonthsLeaderboards,
            'auth_user_monthly' => $authUserMonthlyData, // Auth user's monthly data
            'auth_user_weekly' => $authUserWeeklyData, // Auth user's weekly data
        ];
    }

    public function showLeaderBoard()
    {

        // Get current and previous leaderboards
        $cryptoLeaderboard = $this->getCryptoLeaderBoard();
        $giftLeaderboard = $this->getGiftCardLeaderBoard();

        return response()->json([
            'crypto_leader_board' => $cryptoLeaderboard,
            'giftcard_leader_board' => $giftLeaderboard,
        ]);
    }

    public function showGiftLeaders()
    {

        // Get current and previous leaderboards
        $giftLeaderboard = $this->getGiftCardLeaderBoard();

        return response()->json($giftLeaderboard);
    }
}
