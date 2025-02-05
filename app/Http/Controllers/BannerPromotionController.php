<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BannerPromotion;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreBannerPromotionRequest;
use App\Http\Requests\UpdateBannerPromotionRequest;

class BannerPromotionController extends Controller
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
     * @param  \App\Http\Requests\StoreBannerPromotionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBannerPromotionRequest $request)
    {
        //
    }

    public function addNew(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg',

        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        // save the image
        $imageName = time() . '.' . $request->image->extension();

        $request->image->move(public_path(env('BANNER_IMG_URL') . '/'), $imageName);
        $bannerImage = env('BANNER_IMG_URL') . '/' . $imageName;



        $form = [
            'title' => "Promotion",
            'banner_url' => env('APP_URL') . $bannerImage,
            'promotion_url' => $request->url,
            'status' => 1,
        ];

        BannerPromotion::create($form);

        $response = ["message" => 'Banner Added'];
        return response($response, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BannerPromotion  $bannerPromotion
     * @return \Illuminate\Http\Response
     */
    public function show(BannerPromotion $bannerPromotion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BannerPromotion  $bannerPromotion
     * @return \Illuminate\Http\Response
     */
    public function edit(BannerPromotion $bannerPromotion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateBannerPromotionRequest  $request
     * @param  \App\Models\BannerPromotion  $bannerPromotion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required',

        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        // get banner
        $banner = BannerPromotion::where("id", $request->id)->first();

        // save the image
        if ($request->has('image') && $request->image != null) {
            $imageName = time() . '.' . $request->image->extension();

            $request->image->move(public_path(env('BANNER_IMG_URL') . '/'), $imageName);
            $bannerImage = env('BANNER_IMG_URL') . '/' . $imageName;
            $bannerImageUrl = env('APP_URL') . $bannerImage;
        } else {
            $bannerImageUrl = $banner->banner_url;
        }


        $form = [
            'banner_url' => $bannerImageUrl,
            'promotion_url' => $request->url,
        ];

        BannerPromotion::find($request->id)->update($form);

        $response = ["message" => 'Banner Updated'];
        return response($response, 200);
    }

    public function remove(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => 'required',

        ]);

        BannerPromotion::find($request->id)->update(["status" => 0]);

        $response = ["message" => 'Banner Updated'];
        return response($response, 200);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BannerPromotion  $bannerPromotion
     * @return \Illuminate\Http\Response
     */
    public function destroy(BannerPromotion $bannerPromotion)
    {
        //
    }
}
