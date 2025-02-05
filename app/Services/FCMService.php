<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\User;

class FCMService
{
    public static function send($token, $notification)
    {
        $user = User::where('fcm', $token)->first();

        if ($user) {
            $user_id = $user->id;

            $body = [
                "target_channel" => "push",
                "include_aliases" => ["external_id" => ["$user_id"]],
                "app_id" => "9b7e3b38-f12a-4267-964d-93bc82a4ebaf",
                "headings" => [
                    "en" => $notification['title']
                ],
                "contents" => [
                    "en" => $notification['body']
                ],
            ];

            $response = Http::withHeaders(['Authorization' => "Key os_v2_app_tn7dwohrfjbgpfsnso6ifjhlv5wj5r3fdtjemqvx5kv4jaax36jp4du5tci2b6njifhwsn22rwub66r3w4xekqp3wqdc6jkzcw5ycoi", 'Content-Type' => 'application/json'])->post('https://api.onesignal.com/notifications', $body);
        }
    }

    public static function sendOneSignal($user_id, $notification)
    {

        $body = [
            "target_channel" => "push",
            "include_aliases" => ["external_id" => ["$user_id"]],
            "app_id" => "9b7e3b38-f12a-4267-964d-93bc82a4ebaf",
            "headings" => [
                "en" => $notification['title']
            ],
            "contents" => [
                "en" => $notification['body']
            ],
        ];

        $response = Http::withHeaders(['Authorization' => "Key os_v2_app_tn7dwohrfjbgpfsnso6ifjhlv5wj5r3fdtjemqvx5kv4jaax36jp4du5tci2b6njifhwsn22rwub66r3w4xekqp3wqdc6jkzcw5ycoi", 'Content-Type' => 'application/json'])->post('https://api.onesignal.com/notifications', $body);
    }

    public static function sendToAdmins($notification)
    {

        $body = [
            "target_channel" => "push",
            "included_segments" => ["Total Subscriptions"],
            "app_id" => "",
            "headings" => [
                "en" => $notification['title']
            ],
            "contents" => [
                "en" => $notification['body']
            ],
        ];

        $response = Http::withHeaders(['Authorization' => "Basic MTR...", 'Content-Type' => 'application/json'])->post('https://api.onesignal.com/notifications', $body);
    }


    public static function sendToAllUsers($notification)
    {


        $body = [
            "target_channel" => "push",
            "included_segments" => ["Total Subscriptions"],
            "app_id" => "9b7e3b38-f12a-4267-964d-93bc82a4ebaf",
            "headings" => [
                "en" => $notification['title']
            ],
            "contents" => [
                "en" => $notification['body']
            ],
        ];

        $response = Http::withHeaders(['Authorization' => "Key os_v2_app_tn7dwohrfjbgpfsnso6ifjhlv5wj5r3fdtjemqvx5kv4jaax36jp4du5tci2b6njifhwsn22rwub66r3w4xekqp3wqdc6jkzcw5ycoi", 'Content-Type' => 'application/json'])->post('https://api.onesignal.com/notifications', $body);
    }
}
