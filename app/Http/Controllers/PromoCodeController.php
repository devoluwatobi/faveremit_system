<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PromoCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $all_promos =  PromoCode::where("deleted_at", null)->get();
        return response([
            "message" => "promos fetched successfully",
            "promo_codes" => $all_promos,
        ], 200);
    }

    public function fetch($code)
    {
        $promo =  PromoCode::where("deleted_at", null)->where("code", $code)->whereDate('starts', '<=', date('Y-m-d H:i:s'))->whereDate('ends', '>', date('Y-m-d H:i:s') )->select('id', 'code', 'gain', 'starts', 'ends', 'use_count')->first();
        if (!$promo) {
            return response(
                [
                    "message" => "promos does not exist or unavailable",
                    "promo_code" => $promo,
                ],
                422
            );
        }
        return response([
            "message" => "promos fetched successfully",
            "promo_code" => $promo,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'gain' => 'required',
            'ends' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        
        $form = $request->all();
        $form['ends'] = Carbon::parse($request->ends)->format('Y-m-d');
        
        if($request->starts){
            $form['starts'] = Carbon::parse($request->starts)->format('Y-m-d');
        }
        
        PromoCode::create($form);
        
        $all_promos =  PromoCode::where("deleted_at", null)->get();
        return response([
            "message" => "promos fetched successfully",
            "promo_codes" => $all_promos,
        ], 200);
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
     * @param  \App\Models\PromoCode  $promoCode
     * @return \Illuminate\Http\Response
     */
    public function show(PromoCode $promoCode)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PromoCode  $promoCode
     * @return \Illuminate\Http\Response
     */
    public function edit(PromoCode $promoCode)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PromoCode  $promoCode
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PromoCode $promoCode)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PromoCode  $promoCode
     * @return \Illuminate\Http\Response
     */
    public function destroy(PromoCode $promoCode)
    {
        //
    }
}
