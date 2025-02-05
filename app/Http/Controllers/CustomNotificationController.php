<?php

namespace App\Http\Controllers;

use App\Models\CustomNotification;
use Illuminate\Http\Request;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CustomNotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $notifications = CustomNotification::whereDate('expires_at', '>', now())->orderBy('created_at', 'desc')->get();

        return response([
            "message" => "notifications gotten Successfuly",
            "notifications" => $notifications,
        ]);
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
     * @param  \App\Models\CustomNotification  $customNotification
     * @return \Illuminate\Http\Response
     */
    public function show(CustomNotification $customNotification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CustomNotification  $customNotification
     * @return \Illuminate\Http\Response
     */
    public function edit(CustomNotification $customNotification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CustomNotification  $customNotification
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CustomNotification $customNotification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CustomNotification  $customNotification
     * @return \Illuminate\Http\Response
     */
    public function destroy(CustomNotification $customNotification)
    {
        //
    }

    public function sendPushToUsers(Request $request)
    {

        $user = auth('api')->user();
        // validate details
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
        ]);

        // $sent_notif;
        // if($request->should_stay){
        //   $sent_notif = CustomNotification::create([
        //         'created_by' => $user->id,
        //         'title'  => $request->title,
        //         'type' => $request->type ?? "info",
        //         'content' => $request->description,
        //         'expires_at' => Carbon::parse($request->expires_at)->format('Y-m-d'),
        //     ]);
        // }

        try {
            FCMService::sendToAllUsers(

                [
                    'title' => $request->title,
                    'body' => $request->description,
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
            return response(['message' => 'Push Notification Failed => ' .  $e->getMessage()], 422);
        }


        // return response with message and data

        return response(['message' => 'Push Sent successfully', "notification" => " "], 200);
    }
}
