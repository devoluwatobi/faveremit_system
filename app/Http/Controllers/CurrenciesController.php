<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCurrenciesRequest;
use App\Http\Requests\UpdateCurrenciesRequest;
use App\Models\Currencies;

class CurrenciesController extends Controller
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
     * @param  \App\Http\Requests\StoreCurrenciesRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCurrenciesRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Currencies  $currencies
     * @return \Illuminate\Http\Response
     */
    public function show(Currencies $currencies)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Currencies  $currencies
     * @return \Illuminate\Http\Response
     */
    public function edit(Currencies $currencies)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateCurrenciesRequest  $request
     * @param  \App\Models\Currencies  $currencies
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCurrenciesRequest $request, Currencies $currencies)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Currencies  $currencies
     * @return \Illuminate\Http\Response
     */
    public function destroy(Currencies $currencies)
    {
        //
    }
}
