<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
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
     * @param  \App\Http\Requests\StoreServiceRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'icon' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'transaction_icon' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'service_type' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $imageName = time() . '.' . $request->icon->extension();

        $request->icon->move(public_path(env('SERVICES_IMAGE_URL')), $imageName);
        $url = env('SERVICES_IMAGE_URL')  . $imageName;

        $service = Service::create([
            'title' => $request->title,
            'description' => $request->description,
            'service_type' => $request->service_type,
            'icon' => $url,
        ]);

        return response($service, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function show(Service $service)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function edit(Service $service)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateServiceRequest  $request
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required',
            'title' => 'required',
            'description' => 'required',

        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        if ($request->icon) {
            $validator = Validator::make($request->all(), [
                'icon' => 'image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $imageName = time() . '.' . $request->icon->extension();

            $request->icon->move(public_path(env('SERVICES_IMAGE_URL')), $imageName);
            $url = env('SERVICES_IMAGE_URL')  . $imageName;
            $service = Service::find($request->service_id)->update([
                'icon' => $url,
            ]);
        }

        if ($request->transaction_icon) {
            $validator = Validator::make($request->all(), [
                'transaction_icon' => 'image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $imageName = time() . '.' . $request->transaction_icon->extension();

            $request->transaction_icon->move(public_path(env('SERVICES_IMAGE_URL')), $imageName);
            $url = env('SERVICES_IMAGE_URL')  . $imageName;
            $service = Service::find($request->service_id)->update([
                'transaction_icon' => $url,
            ]);
        }


        $service = Service::find($request->service_id)->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return response($service, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function destroy(Service $service)
    {
        //
    }
}
