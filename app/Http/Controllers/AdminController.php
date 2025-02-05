<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorWallet;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        return response([], 200);
    }

    // wallet
    public function wallet()
    {
        $user = auth('api')->user();
        $store = $user->store()->first();

        $my_earnings = $store->orders()->where('status', '<=', 3)->sum('price');
        $total = Order::where('status', '<=', 3)->sum('price');
        $pending_withdraws = VendorWallet::where('status', 0)->sum('amount');
        $paidout = VendorWallet::where('status', 1)->sum('amount');
        $balance = number_format((float) $total -  $paidout, 2);

        $data = [
            'store' => number_format($my_earnings, 2),
            'total' => number_format($total, 2),
            'pending' => number_format($pending_withdraws, 2),
            'paidout' => number_format($paidout, 2),
            'balance' => $balance,
        ];

        return response($data, 200);
    }



    public function getTransactions(Request $request)
    {
        $user = auth('api')->user();
        $query_status = $request->input('status');



        $data  = VendorWallet::where('status', $query_status)->get();

        $trans = [];
        foreach ($data as $transact) {
            $store = Vendor::where('id', $transact->vendor_id)->first();
            $vendor_user = User::where('id', $store->user_id)->first();

            $item['id'] = $transact->id;
            $item['store'] = $store->store_name;
            $item['transaction_ref'] = $transact->transaction_ref;
            $item['amount'] = $transact->amount;
            $item['status'] = $transact->status;
            $item['bank'] = $vendor_user->bank()->first();
            $item['created_at'] = $transact->created_at;
            $trans[] = $item;
        }

        return response($trans, 200);
    }


    public function getVendors(Request $request)
    {

        $query_status = $request->input('status');



        $data  = Vendor::where('status', $query_status)->get();

        $stores = [];

        foreach ($data as $vendor) {
            $vendor_user = User::where('id', $vendor->user_id)->first();

            $item['id'] = $vendor->id;
            $item['store_name'] = $vendor->store_name;
            $item['name'] = $vendor_user->name;
            $item['phone'] = $vendor_user->phone;
            $item['email'] = $vendor_user->email;
            $item['status'] = $vendor->status;
            $item['products'] =  Product::where('vendor_id', $vendor->id)->get()->count();
            $item['orders'] =  Order::where('vendor_id', $vendor->id)->get()->count();
            $item['created_at'] = $vendor->created_at;
            $stores[] = $item;
        }

        return response($stores, 200);
    }


    public function getVendor($id)
    {
        $id = $id;
        $vendor = Vendor::where('id', $id)->first();

        $user = User::where('id', $id)->first();

        $products = $vendor->products()->paginate(20);
        $orders = $vendor->orders()->paginate(20);

        $productsCount = $vendor->products()->get()->count();
        $ordersCount = $vendor->orders()->get()->count();
        $total_earnings = $vendor->orders()->where('status', '<=', 3)->sum('price');

        $count = [
            'productsCount' => $productsCount,
            'ordersCount' => $ordersCount,
            'earnings' => $total_earnings
        ];
        $data = [
            'vendor' => $vendor,
            'user' => $user,
            'products' => $products,
            'orders' => $orders,
            'count' => $count,
        ];


        return response($data, 200);
    }

    public function getUsers(Request $request)
    {

        $data  = User::get();

        $users = [];

        foreach ($data as $user) {
            $item['id'] = $user->id;
            $item['name'] = $user->first_name;
            $item['phone'] = $user->phone;
            $item['email'] = $user->email;
            $item['type'] = $user->type;
            $item['status'] = $user->status;
            $item['orders'] =  Order::where('user_id', $user->id)->get()->count();
            $item['created_at'] = $user->created_at;
            $users[] = $item;
        }

        return response($users, 200);
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
     * @param  \App\Http\Requests\StoreVendorRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreVendorRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Vendor  $vendor
     * @return \Illuminate\Http\Response
     */
    public function show(Vendor $vendor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Vendor  $vendor
     * @return \Illuminate\Http\Response
     */
    public function edit(Vendor $vendor)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateVendorRequest  $request
     * @param  \App\Models\Vendor  $vendor
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Vendor  $vendor
     * @return \Illuminate\Http\Response
     */
    public function destroy(Vendor $vendor)
    {
        //
    }
}
