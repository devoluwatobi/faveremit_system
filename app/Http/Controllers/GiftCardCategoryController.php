<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GiftCardCategory;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreGiftCardCategoryRequest;
use App\Http\Requests\UpdateGiftCardCategoryRequest;

class GiftCardCategoryController extends Controller
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
     * @param  \App\Http\Requests\StoreGiftCardCategoryRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreGiftCardCategoryRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\GiftCardCategory  $giftCardCategory
     * @return \Illuminate\Http\Response
     */
    public function show(GiftCardCategory $giftCardCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\GiftCardCategory  $giftCardCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(GiftCardCategory $giftCardCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateGiftCardCategoryRequest  $request
     * @param  \App\Models\GiftCardCategory  $giftCardCategory
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateGiftCardCategoryRequest $request, GiftCardCategory $giftCardCategory)
    {
        //
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $checker = GiftCardCategory::where('id', $request->id)->first();

        if (!$checker) {
            $response = ["message" => 'Gift card category doesnt exist'];
            return response($response, 404);
        }

        GiftCardCategory::where('id', $request->id)->delete();

        $response = ["message" => 'Gift card category deleted'];
        return response($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\GiftCardCategory  $giftCardCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(GiftCardCategory $giftCardCategory)
    {
        //
    }
}
