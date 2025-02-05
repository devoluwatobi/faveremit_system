<?php

namespace App\Http\Controllers;

use Exception;
use App\Mail\contactMail;
use App\Mail\contactReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class WebContact extends Controller
{
    public function webContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'name' => 'required|string',
            'word' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            // Mail::to("omodunaiya@gmail.com")->send(new contactMail($request->name, $request->word, $request->email));
            Mail::to("omodunaiya@gmail.com")->send(new contactMail($request->name, $request->word, $request->email));
            Mail::to($request->email)->send(new contactReply($request->name, $request->word));
        } catch (Exception $e) {
            Log::error("Error -> " . $e);
            return response()->json(['error' => "Internal Server Error"], 500);
        }
        return response([
            "error" => false,
            "message" => "Email is Sent.",

        ], 200);
    }
}
