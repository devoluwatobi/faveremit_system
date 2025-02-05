<?php

namespace App\Http\Controllers;

use App\Models\GiftCardsCountry;
use App\Http\Requests\StoreGiftCardsCountryRequest;
use App\Http\Requests\UpdateGiftCardsCountryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GiftCardsCountryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
     * @param  \App\Http\Requests\StoreGiftCardsCountryRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required',
            'gift_card_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $checker = GiftCardsCountry::where('country_id', $request->country_id)->where('gift_card_id', $request->gift_card_id)->first();

        if ($checker) {
            $response = ["message" => 'Gift card country already added'];
            return response($response, 200);
        }

        GiftCardsCountry::create($request->toArray());

        $response = ["message" => 'Gift card country added'];
        return response($response, 200);
    }

    public function deactivate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $checker = GiftCardsCountry::where('id', $request->id)->first();

        if (!$checker) {
            $response = ["message" => 'Gift card country doesnt exist'];
            return response($response, 404);
        }

        GiftCardsCountry::where('id', $request->id)->first()->update(["status" => 0]);

        $response = ["message" => 'Gift card country status updated'];
        return response($response, 200);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $checker = GiftCardsCountry::where('id', $request->id)->first();

        if (!$checker) {
            $response = ["message" => 'Gift card country doesnt exist'];
            return response($response, 404);
        }

        GiftCardsCountry::where('id', $request->id)->delete();

        $response = ["message" => 'Gift card country deleted'];
        return response($response, 200);
    }

    public function activate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $checker = GiftCardsCountry::where('id', $request->id)->first();

        if (!$checker) {
            $response = ["message" => 'Gift card country doesnt exist'];
            return response($response, 404);
        }

        GiftCardsCountry::where('id', $request->id)->first()->update(["status" => 1]);

        $response = ["message" => 'Gift card country status updated'];
        return response($response, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\GiftCardsCountry  $giftCardsCountry
     * @return \Illuminate\Http\Response
     */
    public function show(GiftCardsCountry $giftCardsCountry)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\GiftCardsCountry  $giftCardsCountry
     * @return \Illuminate\Http\Response
     */
    public function edit(GiftCardsCountry $giftCardsCountry)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateGiftCardsCountryRequest  $request
     * @param  \App\Models\GiftCardsCountry  $giftCardsCountry
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateGiftCardsCountryRequest $request, GiftCardsCountry $giftCardsCountry)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\GiftCardsCountry  $giftCardsCountry
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $country = GiftCardsCountry::find($id);

        if (!$country) {
            $response = ["message" => 'Gift card country not found'];
            return response($response, 200);
        }
        $country->delete();
        $response = ["message" => 'Gift card country removed'];
        return response($response, 200);
    }
}
