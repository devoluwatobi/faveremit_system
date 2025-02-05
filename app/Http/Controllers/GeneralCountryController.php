<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGeneralCountryRequest;
use App\Http\Requests\UpdateGeneralCountryRequest;
use App\Models\GeneralCountry;

class GeneralCountryController extends Controller
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
     * @param  \App\Http\Requests\StoreGeneralCountryRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreGeneralCountryRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\GeneralCountry  $generalCountry
     * @return \Illuminate\Http\Response
     */
    public function show(GeneralCountry $generalCountry)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\GeneralCountry  $generalCountry
     * @return \Illuminate\Http\Response
     */
    public function edit(GeneralCountry $generalCountry)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateGeneralCountryRequest  $request
     * @param  \App\Models\GeneralCountry  $generalCountry
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateGeneralCountryRequest $request, GeneralCountry $generalCountry)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\GeneralCountry  $generalCountry
     * @return \Illuminate\Http\Response
     */
    public function destroy(GeneralCountry $generalCountry)
    {
        //
    }
}
