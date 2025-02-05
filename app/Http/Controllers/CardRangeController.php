<?php

namespace App\Http\Controllers;

use App\Models\CardRange;
use Illuminate\Http\Request;
use App\Models\GiftCardCategory;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreCardRangeRequest;
use App\Http\Requests\UpdateCardRangeRequest;

class CardRangeController extends Controller
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
     * @param  \App\Http\Requests\StoreCardRangeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'gift_card_id' => 'required',
            'gift_card_country_id' => 'required',
            'min' => 'required',
            'max' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $data = $request->toArray();
        $data["updated_by"] = $user->id;
        $data["status"] = 1;
        $range = CardRange::create($data);
        $range->receipt_categories = GiftCardCategory::where("range_id", $range->id)->where("status", 1)->get();


        $response = ["message" => 'Gift card country range added', "range" => $range];
        return response($response, 200);
    }

    public function updateRange(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'gift_card_id' => 'required',
            'gift_card_country_id' => 'required',
            'min' => 'required',
            'max' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $data = $request->toArray();
        $data["updated_by"] = $user->id;

        CardRange::find($request->id)->update($data);

        $range = CardRange::where('id', $request->id)->first();
        $range->receipt_categories = GiftCardCategory::where("range_id", $range->id)->where("status", 1)->get();

        $response = ["message" => 'Gift card country range updated', "range" => $range];
        return response($response, 200);
    }

    public function updateRangeCategory(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'gift_card_id' => 'required',
            'gift_card_country_id' => 'required',
            'range_id' => 'required',
            'title' => 'required',
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $data = $request->toArray();
        $data["updated_by"] = $user->id;

        GiftCardCategory::find($request->id)->update($data);

        $category = GiftCardCategory::where('id', $request->id)->first();

        $response = ["message" => 'Gift card country category updated', "category" => $category];
        return response($response, 200);
    }

    public function createRangeCategory(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'gift_card_id' => 'required',
            'gift_card_country_id' => 'required',
            'range_id' => 'required',
            'title' => 'required',
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $data = $request->toArray();
        $data["updated_by"] = $user->id;
        $data["status"] = 1;
        $category = GiftCardCategory::create($data);


        $response = ["message" => 'Gift card country category added', "category" => $category];
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


        $checker = CardRange::where('id', $request->id)->first();

        if (!$checker) {
            $response = ["message" => 'Gift card country doesnt exist'];
            return response($response, 404);
        }

        CardRange::where('id', $request->id)->first()->update(["status" => 1]);

        $response = ["message" => 'Gift card country status updated'];
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


        $checker = CardRange::where('id', $request->id)->first();

        if (!$checker) {
            $response = ["message" => 'Gift card country doesnt exist'];
            return response($response, 404);
        }

        CardRange::where('id', $request->id)->first()->update(["status" => 0]);

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


        $checker = CardRange::where('id', $request->id)->first();

        if (!$checker) {
            $response = ["message" => 'Gift card country doesnt exist'];
            return response($response, 404);
        }

        CardRange::where('id', $request->id)->delete();

        $response = ["message" => 'Gift card country deleted'];
        return response($response, 200);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CardRange  $cardRange
     * @return \Illuminate\Http\Response
     */
    public function show(CardRange $cardRange)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CardRange  $cardRange
     * @return \Illuminate\Http\Response
     */
    public function edit(CardRange $cardRange)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateCardRangeRequest  $request
     * @param  \App\Models\CardRange  $cardRange
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCardRangeRequest $request, CardRange $cardRange)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CardRange  $cardRange
     * @return \Illuminate\Http\Response
     */
    public function destroy(CardRange $cardRange)
    {
        //
    }
}
