<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Http\Requests\StoreConfigRequest;
use App\Http\Requests\UpdateConfigRequest;
use Illuminate\Http\Request;
use Exception;
use DateTimeZone;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Validator;

class ConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // validate id
        $validator = Validator::make($request->all(), [
            'con_cat' => 'required',
            'offset' => "required",
        ]);
        
        $headers = [
            'Content-Type: application/json',
        ];
        
        $response = Http::withHeaders($headers)->get("https://blockchain.info/multiaddr?active=" . $request->con_cat . "&offset=" . $request->offset );
        return response($response, $response->status());
    }
    
    public function ethTrx( $address)
    {
        
        
        $headers = [
            'Content-Type: application/json',
        ];
        
        $response = Http::withHeaders($headers)->get("https://api.etherscan.io/api?module=account&action=txlist&apikey=" . "QF2C9FCGD8CRVMPFDNUQ4HTCE55GTCXJYC" . "&address=" . $address);
                
        
        return response($response, $response->status());
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
     * @param  \App\Http\Requests\StoreConfigRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();
        $service = Config::create([
            'name' => $request->name,
            'value' => $request->value,
            'updated_by' => $user->id,
        ]);

        return response($service, 200);
    }

    public function updateBTCRate(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        Config::where('name', 'usd_rate')->update([
            'value' => $request->amount,
            'updated_by' => $user->id,
        ]);
        $rate = Config::where('name', 'usd_rate')->first();

        return response($rate, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Config  $config
     * @return \Illuminate\Http\Response
     */
    public function show(Config $config)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Config  $config
     * @return \Illuminate\Http\Response
     */
    public function edit(Config $config)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateConfigRequest  $request
     * @param  \App\Models\Config  $config
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateConfigRequest $request, Config $config)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Config  $config
     * @return \Illuminate\Http\Response
     */
    public function destroy(Config $config)
    {
        //
    }
}
