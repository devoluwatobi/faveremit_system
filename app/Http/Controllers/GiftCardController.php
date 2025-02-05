<?php

namespace App\Http\Controllers;

use App\Models\country;
use App\Models\GiftCard;
use App\Models\CardRange;
use Illuminate\Http\Request;
use App\Models\GiftCardCategory;
use App\Models\GiftCardsCountry;
use Illuminate\Support\Facades\DB;
use App\Models\GiftCardTransaction;
use Illuminate\Support\Facades\Validator;

class GiftCardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = GiftCard::where('status', 1)->select('id', 'title', 'image', 'brand_logo')->get();

        $giftcards = [];

        GiftCardTransaction::where('status', 1)->select("gift_card_id");

        $dataz =
        DB::table('gift_card_transactions')->select('gift_card_id', DB::raw('COUNT(gift_card_id) AS occurrences'))
        ->groupBy('gift_card_id')
        ->orderBy('occurrences', 'DESC')
            ->limit(6)
            ->get();


        foreach ($data as $card) {
            $item['id'] = $card->id;
            $item['title'] = $card->title;
            $item['image'] = env('APP_URL') . $card->image;
            $item['brand_logo'] = env('APP_URL') . $card->brand_logo;
            $firstCountry = GiftCardsCountry::where('gift_card_id', $card->id)->where('deleted_at', null)->first();
            $item['popular'] = false;

            foreach ($dataz as $pop) {
                if ($pop->gift_card_id == $card->id) {
                    $item['popular'] = true;
                }
            }
            if ($firstCountry) {
                $giftcards[] = $item;
            }
        }

        // return response($dataz, 200);
        return response($giftcards, 200);
    }
    public function AdminIndex()
    {
        $data = GiftCard::where('status', "!=", 99)->get();

        $giftcards = [];

        foreach ($data as $card) {
            $item['id'] = $card->id;
            $item['title'] = $card->title;
            $item['image'] = env('APP_URL') . $card->image;
            $item['brand_logo'] = env('APP_URL') . $card->brand_logo;
            $item['status'] = $card->status;
            $item['created_at'] = $card->created_at->diffForHumans();

            $giftcards[] = $item;
        }

        return response($giftcards, 200);
    }
    
    public function AdminIndex2()
    {
        $data = GiftCard::where('status', "!=", 0)->get();

        $giftcards = [];

        foreach ($data as $card) {
            $item['id'] = $card->id;
            $item['title'] = $card->title;
            $item['image'] = env('APP_URL') . $card->image;
            $item['brand_logo'] = env('APP_URL') . $card->brand_logo;
            $item['status'] = strval($card->status);
            $item['created_at'] = $card->created_at->diffForHumans();
            $item['confirm_min'] = $card->confirm_min;
            $item['confirm_max'] = $card->confirm_max;
            
            // countries
            $rawCoun = GiftCardsCountry::where('gift_card_id', $card->id)->where('deleted_at', null)->where('status', "!=", "0")->get();

            $countries = [];
            foreach ($rawCoun as $coun) {
                $country = country::where('id', $coun->country_id)->first();
                $ranges = CardRange::where('gift_card_country_id', $coun->id)->where('deleted_at', null)->get();
                $cItem['id'] = $coun->id;
                $cItem['status'] = strval($coun->status);
                $cItem['name'] = $country->name;
                $cItem['image'] = env('APP_URL') . '/images/flags/' . strtolower($country->iso) . '.png';
                $cItem['iso'] = $country->iso;
                
                
                
            $complete_ranges = [];
            foreach ($ranges as $range) {
                $r_cat = GiftCardCategory::where("range_id", $range->id)->where('deleted_at', null)->where("status", 1)->get();
                 foreach ($r_cat as $r){
                    $r['updated_by'] = strval($r->updated_by);
                    $r['status'] = strval($r->status);
                    $r['range_id'] = strval($r->range_id);
                    $r['gift_card_country_id'] = strval($r->gift_card_country_id);
                    $r['gift_card_id'] = strval($r->gift_card_id);
                 }
                $range['receipt_categories'] = $r_cat;
                $range['gift_card_id'] = strval($range->gift_card_id);
                $range['gift_card_country_id'] = strval($range->gift_card_country_id);
                $range['status'] = strval($range->status);
                $range['min'] = strval($range->min);
                $range['max'] = strval($range->max);
                $range['updated_by'] = strval($range->updated_by);
                $complete_ranges[] = $range;
            }
                // get the count of ranges
                $cItem['ranges'] = $complete_ranges;
    
                $countries[] = $cItem;
            }
            
            $item['$countries'] = $countries;

            $giftcards[] = $item;
        }

        return response($giftcards, 200);
    }


    public function AdminSingleCard($id)
    {
        $data = GiftCard::where('id', $id)->first();

        //get the country name
        $rawCoun = GiftCardsCountry::where('gift_card_id', $data->id)->where('deleted_at', null)->get();

        $countries = [];
        foreach ($rawCoun as $coun) {
            $country = country::where('id', $coun->country_id)->first();
            $ranges = CardRange::where('gift_card_country_id', $coun->id)->where('deleted_at', null)->get();
            $cItem['id'] = $coun->id;
            $cItem['status'] = $coun->status;
            $cItem['name'] = $country->name;
            $cItem['image'] = env('APP_URL') . '/images/flags/' . strtolower($country->iso) . '.png';
            $cItem['iso'] = $country->iso;
            // get the count of ranges
            $cItem['ranges'] = $ranges->count();

            $countries[] = $cItem;
        }

        $item['id'] = $data->id;
        $item['title'] = $data->title;
        $item['image'] = env('APP_URL') . $data->image;
        $item['brand_logo'] = env('APP_URL') . $data->brand_logo;
        $item['status'] = $data->status;
        $item['created_at'] = $data->created_at->diffForHumans();
        $item['countries'] = $countries;

        return response($item, 200);
    }

    public function AdminSingleCountry($id)
    {


        $data = GiftCardsCountry::where('id', $id)->first();
        $giftCard = GiftCard::where('id', $data->gift_card_id)->first();

        $country = country::where('id', $data->country_id)->first();
        $ranges = CardRange::where('gift_card_country_id', $data->id)->where('deleted_at', null)->get();

        //create an object with data, country and ranges

        $complete_ranges = [];
        foreach ($ranges as $range) {
            $range['receipt_categories'] = GiftCardCategory::where("range_id", $range->id)->where('deleted_at', null)->where("status", 1)->get();
            $complete_ranges[] = $range;
        }

        $response = [
            'id' => $data->id,
            'card_title' => $giftCard->title,
            'country' => $country->name,
            'image' => env('APP_URL') . '/images/flags/' . strtolower($country->iso) . '.png',
            'iso' => $country->iso,
            'status' => $data->status,
            'ranges' => $complete_ranges,
        ];

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
     * @param  \App\Http\Requests\StoreGiftCardRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg',
            'brand_logo' => 'required|image|mimes:jpeg,png,jpg',

        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        // save the image
        $imageName = time() . '.' . $request->image->extension();

        $request->image->move(public_path(env('GIFTCARD_CARD_IMG') . '/'), $imageName);
        $giftcardImage = env('GIFTCARD_CARD_IMG') . '/' . $imageName;


        // save the image
        $brandLogoName = time() . '.' . $request->brand_logo->extension();

        $request->brand_logo->move(public_path(env('GIFTCARD_CARD_IMG')  . '/'), $brandLogoName);
        $giftcardLogo = env('GIFTCARD_CARD_IMG')  . '/' . $brandLogoName;



        $form = [
            'title' => $request->title,
            'image' => $giftcardImage,
            'brand_logo' => $giftcardLogo,
            'service_id' => 0,
            'updated_by' => $user->id,
        ];

        GiftCard::create($form);

        $response = ["message" => 'Gift card added'];
        return response($response, 200);
    }

    public function update(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'title' => 'required',

        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $giftCard = GiftCard::where('id', $request->id)->first();
        if ($request->has('image')) {
            // save the image
            $imageName = time() . '.' . $request->image->extension();

            $request->image->move(public_path(env('GIFTCARD_CARD_IMG') . '/'), $imageName);
            $giftcardImage = env('GIFTCARD_CARD_IMG') . '/' . $imageName;

            if ($giftCard->image) {
                $old_image = public_path($giftCard->image);
                if (file_exists($old_image)) {
                    unlink($old_image);
                }
            }

            GiftCard::find($request->id)->update(['image' => $giftcardImage]);
            $user->photo = env('APP_URL') . $giftcardImage;
        }

        if ($request->has('brand_logo')) {
            // save the image
            $brandLogoName = time() . '.' . $request->brand_logo->extension();

            $request->brand_logo->move(public_path(env('GIFTCARD_CARD_IMG')  . '/'), $brandLogoName);
            $giftcardLogo = env('GIFTCARD_CARD_IMG')  . '/' . $brandLogoName;


            if ($giftCard->brand_logo) {
                $old_logo = public_path($giftCard->brand_logo);
                if (file_exists($old_logo)) {
                    unlink($old_logo);
                }
            }

            GiftCard::find($request->id)->update(['brand_logo' => $giftcardLogo]);
            $user->photo = env('APP_URL') . $giftcardLogo;
        }



        $form = [
            'title' => $request->title,
            'service_id' => 1,
            'updated_by' => $user->id,
        ];

        GiftCard::find($request->id)->update($form);

        $response = ["message" => 'Gift card updated'];
        return response($response, 200);
    }


    public function cardData($id)
    {
        $giftCard = GiftCard::where('id', $id)->first();

        $countries = $giftCard->cardCountries()->where('status', 1)->select('id', 'gift_card_id', 'country_id')->get();


        $data = [];
        foreach ($countries as $country) {

            $ranges = CardRange::where('gift_card_id', $country->gift_card_id)->where('deleted_at', null)
                ->where('gift_card_country_id', $country->id)->where('status', 1)
                ->select('id', 'gift_card_id', 'gift_card_country_id', 'max', 'min')
                ->get();

            foreach ($ranges as $range) {
                $categories = GiftCardCategory::where('range_id', $range->id)->where('deleted_at', null)->select('id', 'amount', 'title', 'range_id', 'gift_card_id', 'gift_card_country_id')->get();
                $range['categories'] = $categories;
            }

            $firstRange = CardRange::where('gift_card_id', $country->gift_card_id)->where('deleted_at', null)
                ->where('gift_card_country_id', $country->id)->first();

            $singleCountry = country::where('id', $country->country_id)->first();

            $item['id'] = $country->id;
            $item['gift_card_id'] = $country->gift_card_id;
            $item['gift_card_country_image'] = env('APP_URL') . '/images/flags/' . strtolower($singleCountry->iso) . '.png';
            $item['country_id'] = $country->country_id;
            $item['country_name'] = $singleCountry->name;
            $item['country_iso'] = $singleCountry->iso;
            $item['range'] = $ranges;

            if ($firstRange) {
                $data[] = $item;
            }


        }


        return response($data, 200);
    }
    
    public function cardData2($id)
    {
        $giftCard = GiftCard::where('id', $id)->first();

        $countries = $giftCard->cardCountries()->where('status', 1)->select('id', 'gift_card_id', 'country_id')->get();


        $data = [];
        foreach ($countries as $country) {

            $ranges = CardRange::where('gift_card_id', $country->gift_card_id)->where('deleted_at', null)
                ->where('gift_card_country_id', $country->id)->where('status', 1)
                ->select('id', 'gift_card_id', 'gift_card_country_id', 'max', 'min')
                ->get();

            foreach ($ranges as $range) {
                $categories = GiftCardCategory::where('range_id', $range->id)->where('deleted_at', null)->select('id', 'amount', 'title', 'range_id', 'gift_card_id', 'gift_card_country_id')->get();
                $range['categories'] = $categories;
            }

            $firstRange = CardRange::where('gift_card_id', $country->gift_card_id)->where('deleted_at', null)
                ->where('gift_card_country_id', $country->id)->first();

            $singleCountry = country::where('id', $country->country_id)->first();

            $item['id'] = $country->id;
            $item['gift_card_id'] = $country->gift_card_id;
            $item['gift_card_country_image'] = env('APP_URL') . '/images/flags/' . strtolower($singleCountry->iso) . '.png';
            $item['country_id'] = $country->country_id;
            $item['country_name'] = $singleCountry->name;
            $item['country_iso'] = $singleCountry->iso;
            $item['range'] = $ranges;

            if ($firstRange) {
                $data[] = $item;
            }


        }


        return response($data, 200);
    }

    public function deactivate(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required',

        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $giftCard = GiftCard::where('id', $request->id)->first();

        if (!$giftCard) {
            return response("giftcard with the id not found", 411);
        } else {
            $giftCard = GiftCard::where('id', $request->id)->first()->update(['status' => 0, 'updated_by' => $user->id]);
        }



        return response($giftCard, 200);
    }

    public function activate(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required',

        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $giftCard = GiftCard::where('id', $request->id)->first();

        if (!$giftCard) {
            return response("giftcard with the id not found", 411);
        } else {
            $giftCard = GiftCard::where('id', $request->id)->first()->update(['status' => 1, 'updated_by' => $user->id]);
        }



        return response($giftCard, 200);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\GiftCard  $giftCard
     * @return \Illuminate\Http\Response
     */
    public function show(GiftCard $giftCard)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\GiftCard  $giftCard
     * @return \Illuminate\Http\Response
     */
    public function edit(GiftCard $giftCard)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateGiftCardRequest  $request
     * @param  \App\Models\GiftCard  $giftCard
     * @return \Illuminate\Http\Response
     */
    // public function update(Request $request, GiftCard $giftCard)
    // {
    //     //
    // }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\GiftCard  $giftCard
     * @return \Illuminate\Http\Response
     */
    public function destroy(GiftCard $giftCard)
    {
        //
    }
}
