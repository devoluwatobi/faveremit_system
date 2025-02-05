<?php

namespace App\Http\Controllers\Auth;

use Exception;
use Carbon\Carbon;

use \App\Models\User;
use App\Models\Wallet;
use \App\Models\Vendor;
use Twilio\Rest\Client;
use Illuminate\Support\Str;
use App\Mail\VerifyOtpEmail;
use App\Models\RewardWallet;
use App\Services\FCMService;
use App\Services\SMSService;
use Illuminate\Http\Request;
use App\Models\DeleteRequest;
use App\Mail\LoginNotification;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\RewardWalletTransaction;
use App\Models\UserDevice;
use App\Services\MailgunService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class ApiAuthController extends Controller

{

    public function forgetPass(Request $request)
    {
        $phone =  $request->input('phone');
        //You can add validation login here
        $user = DB::table('users')->where('phone', '=', $phone)->first();
        //Check if the user exists
        if (!$user) {
            $response = [
                'message' => "User is not registered",
            ];
            return response($response, 422);
        }
        $token = random_int(100000, 999999);


        //Create Password Reset Token
        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        $receiverNumber = $request->phone;
        $message = "Your Faveremit verification code is " . $token;

        try {

            SMSService::sendOTP($receiverNumber, $token);
            Mail::to($user->email)->send(new VerifyOtpEmail($user->email, $token, $user->first_name));
        } catch (Exception $e) {
            Log::error("Error: " . $e->getMessage());
        }

        $response = [
            'message' => "Password reset code sent",
            'otp' => $token,
        ];
        return response($response, 200);
    }

    public function forgetEmailPass(Request $request)
    {
        $email =  $request->input('email');
        //You can add validation login here
        $user = DB::table('users')->where('email', '=', $email)->first();
        //Check if the user exists
        if (!$user) {
            $response = [
                'message' => "User is not registered",
            ];
            return response($response, 422);
        }
        $token = random_int(100000, 999999);


        //Create Password Reset Token
        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        $receiverNumber = $request->phone;
        $message = "Your Faveremit verification code is " . $token;

        try {

            // SMSService::sendOTP($receiverNumber, $token);
            Mail::to($user->email)->send(new VerifyOtpEmail($user->email, $token, $user->first_name,));
        } catch (Exception $e) {
            Log::error("Error: " . $e->getMessage());
        }

        $response = [
            'message' => "Password reset code sent",
            'otp' => $token,
        ];
        return response($response, 200);
    }


    public function updateFcm(Request $request)
    {
        // validate the request...
        $validator = Validator::make($request->all(), [
            'fcm' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }

        $user = auth('api')->user();

        // if user not auth then return error
        if (!$user) {
            return response(['errors' => ['User not found']], 422);
        }
        $user->fcm = $request->fcm;
        $user->save();
        return response($user, 200);
    }

    public function sendMail()
    {
        $details = [
            'name' => 'Andrew Richard',
            'body' => 'This is for testing email using smtp'
        ];

        Mail::to('omodunaiyad@gmail.com')->send(new \App\Mail\NewUserNotification($details));

        Log::info("Email is Sent.");
    }

    public function testMailList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response(
                [
                    'error' => true,
                    'message' => $validator->errors()->all()
                ],
                422
            );
        }

        try {
            MailgunService::addToMailList($request->name, $request->email);
        } catch (Exception $e) {
            Log::error("Mail List Error: " . $e);
            return response(
                [
                    'error' => true,
                    'message' => "Internal Server Error",
                    'the_error' => $e
                ],
                500
            );
        }

        return response(
            [
                'error' => false,
                'message' => "Email Added to mail List",
            ],
            200
        );
    }

    public function getMailList(Request $request)
    {
        $array =   User::where("email", "<>", null)->pluck('email')->toArray();

        return response(
            [
                'error' => false,
                'message' => "Email Added to mail List",
                'data' => $array,
            ],
            200
        );
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'first_name' => 'required|string|max:255',
            // 'last_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:15|unique:users',
            'username' => 'required|string|max:15|unique:users',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response(
                [
                    'error' => true,
                    'message' => $validator->errors()->all()
                ],
                422
            );
        }

        $parts = preg_split('/\s+/', $request['name']);

        if (count($parts) > 1) {
            $request['first_name'] = $parts[0];
            $request['last_name'] = $parts[1];
        } else {
            return response(
                [
                    'error' => true,
                    'message' => "Full Name Required"
                ],
                422
            );
        }
        if (!str_contains($request->phone, '+')) {
            $request->phone = "+" . $request->phone;
        }
        $request['password'] = Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        $userraw = User::create($request->toArray());
        $user = user::where('id', $userraw->id)->first();
        $token = $user->createToken('Faveremit Password Grant Client')->accessToken;
        // send mail to user
        try {
            Mail::to($user->email)->send(new \App\Mail\NewUserNotification($user->first_name));
        } catch (Exception $e) {
            Log::error("Welcome Email Error: " . $e);
        }
        // create the user wallet
        $wallet =  [
            'user_id' => $user->id,
            'balance' => 0.0,
        ];


        $rewardWallet =  [
            'user_id' => $user->id,
            'balance' => 0.0,
        ];

        Wallet::create($wallet);

        RewardWallet::create($rewardWallet);

        $otp_token =  random_int(100000, 999999);

        $receiverNumber = $request->phone;
        $message = "Your Faveremit verification code is " . $otp_token;

        // Handle Referral
        if ($request->referrer) {
            $referr = User::where("username", $request->referrer)->first();
            if ($referr) {

                RewardWalletTransaction::create([
                    "amount" => 5,
                    "user_id" => $referr->id,
                    "type" => "referral",
                    "referred_user_id" => $user->id,
                    "status" => 1,
                ]);

                $referrer_reward_wallet = RewardWallet::where("user_id", $referr->id)->first();
                $referrer_reward_wallet->balance = $referrer_reward_wallet->balance + 5;
                $referrer_reward_wallet->save();

                try {
                    FCMService::send(
                        $referr->fcm,
                        [
                            'title' => '5 Reward Points Earned 🚀',
                            'body' => "You just earned 5 reward ponts from a successful referral.",
                        ]
                    );
                } //catch exception
                catch (Exception $e) {
                    Log::error('Message: ' . $e->getMessage());
                }
            }
        }

        try {

            // SMSService::sendOTP($user->phone, $otp_token);
            Mail::to($user->email)->send(new \App\Mail\VerifyOtpEmail($user->email, $otp_token, $user->first_name,));
        } catch (Exception $e) {
            Log::error("Error: " . $e->getMessage());
        }

        $response = [
            'token' => $token,
            'user' => $user,
            'otp' => $otp_token,
        ];
        return response($response, 200);
    }

    public function checkUsername(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:15|unique:users',
        ]);


        if ($validator->fails()) {
            return response([
                'error' => true,
                'message' => $validator->errors()->all()
            ], 422);
        }

        $owner = User::where("username", $request->username)->first();
        if ($owner) {
            return response([
                'error' => true,
                'message' => "username unavailable"
            ], 422);
        }

        $response = [
            'error' => false,
            'message' => "Username is available",
        ];
        return response($response, 200);
    }

    public function checkReferrer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:15',
        ]);


        if ($validator->fails()) {
            return response([
                'error' => true,
                'message' => $validator->errors()->all()
            ], 400);
        }

        $owner = User::where("username", $request->username)->first();
        if ($owner) {
            return response([
                'error' => false,
                'user' => $owner->name,
                'message' => "Referrer fetched sucessfully"
            ], 200);
        }

        $response = [
            'error' => true,
            'message' => "User not found",
        ];
        return response($response, 404);
    }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:15',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = User::where("phone", $request->phone)->first();

        $otp_token =  random_int(100000, 999999);

        $receiverNumber = $request->phone;
        $message = "Your Faveremit verification code is " . $otp_token;

        try {

            SMSService::sendOTP($receiverNumber, $otp_token);
            if ($user) {
                Mail::to($user->email)->send(new \App\Mail\VerifyOtpEmail($user->email, $otp_token, $user->first_name,));
            }
        } catch (Exception $e) {
            Log::error("Error: " . $e->getMessage());
        }


        $response = [
            'token' => $otp_token,
            'message' => 'otp Sent'
        ];
        return response($response, 200);
    }

    public function resendEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = User::where("email", $request->email)->first();

        $otp_token =  random_int(100000, 999999);

        $receiverNumber = $request->phone;
        $message = "Your Faveremit verification code is " . $otp_token;

        try {

            if ($user) {
                Mail::to($user->email)->send(new \App\Mail\VerifyOtpEmail($user->email, $otp_token, $user->first_name,));
            }
        } catch (Exception $e) {
            Log::error("Error: " . $e->getMessage());
        }


        $response = [
            'token' => $otp_token,
            'message' => 'otp Sent'
        ];
        return response($response, 200);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required',
            'phone' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if ($user) {
            $form = ['phone_verified_at' =>  date("Y-m-d H:i:s", strtotime('now'))];
            User::where('id', $user->id)->update($form);
            $response = ['message' => 'Number Verified'];
            return response($response, 200);
        } else {
            $response = ['message' => 'Number not registered'];
            return response($response, 422);
        }
    }

    public function verifyEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required',
            'email' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $form = [
                // 'phone_verified_at' =>  date("Y-m-d H:i:s", strtotime('now')),
                'email_verified_at' =>  date("Y-m-d H:i:s", strtotime('now')),
            ];
            User::where('id', $user->id)->update($form);
            $response = ['message' => 'Email Verified'];
            return response($response, 200);
        } else {
            $response = ['message' => 'Email not registered'];
            return response($response, 422);
        }
    }

    public function changeRole(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'role' => 'required',
        ]);

        //Customer = 0, Admin = 1, SubAdmin = 2, SubAdmin Pro = 3, Super Admin = 99
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        if ($user->role < 2) {
            $response = ["error" => true, "message" => "You dont have permission to perform this action"];
            return response($response, 422);
        }

        $email_owner = User::where('email', $request->email)->first();
        $phone_owner = User::where('phone', $request->phone)->first();



        if (!$email_owner || !$phone_owner || $email_owner->id != $phone_owner->id) {
            $response = ["message" => "Invalid Credentials"];
            return response($response, 422);
        }


        $user = User::find($email_owner->id)->update(["role" => $request->role]);
        $response = [
            'message' => 'Role updated'
        ];
        return response($response, 200);
    }


    public function deactivateAccount(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'user_id' => 'required|integer',
        ]);

        //Customer = 0, Staff = 0, Super Admin = 2
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        if ($user->role < 1) {
            $response = ["error" => true, "message" => "You dont have permission to perform this action"];
            return response($response, 422);
        }

        $email_owner = User::where('email', $request->email)->first();
        $phone_owner = User::where('phone', $request->phone)->first();



        if (!$email_owner || !$phone_owner || $email_owner->id != $phone_owner->id) {
            $response = ["message" => "Invalid Credentials"];
            return response($response, 422);
        }


        $user = User::where("id", $request->user_id)->update(["status" => 0]);
        $response = [
            'message' => 'Account Deactivated'
        ];
        return response($response, 200);
    }

    public function activateAccount(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'user_id' => 'required|integer',
        ]);

        //Customer = 0, Staff = 0, Super Admin = 2
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        if ($user->role < 1) {
            $response = ["error" => true, "message" => "You dont' have permission to perform this action"];
            return response($response, 422);
        }

        $email_owner = User::where('email', $request->email)->first();
        $phone_owner = User::where('phone', $request->phone)->first();



        if (!$email_owner || !$phone_owner || $email_owner->id != $phone_owner->id) {
            $response = ["message" => "Invalid Credentials"];
            return response($response, 422);
        }


        $user = User::where("id", $request->user_id)->update(["status" => 1]);
        $response = [
            'message' => 'Account Activated'
        ];
        return response($response, 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response(
                [
                    'error' => true,
                    'message' => $validator->errors()->first()
                ],
                422
            );
        }
        if (!str_contains($request->phone, '+')) {
            $request->phone = "+" . $request->phone;
        }
        $user = User::where('phone', $request->phone)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                if (true) {

                    if ($user->role < 99) {
                        $user->tokens()->delete();
                    }

                    // if user has photo then return it else return default
                    if ($user->photo) {
                        $user->photo = env('APP_URL') . $user->photo;
                    }
                    $token = $user->createToken('Faveremit Password Grant Client')->accessToken;

                    $response = ['token' => $token, 'user' => $user];
                    return response($response, 200);
                } else {
                    $response = ["message" => "Account Deactivated. Contact support for more information"];
                    return response($response, 422);
                }
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {

            $response = [
                "error" => true,
                "message" => 'User does not exist'
            ];
            return response($response, 422);
        }
    }

    public function loginEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response(
                [
                    'error' => true,
                    'message' => $validator->errors()->first()
                ],
                422
            );
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                if (true) {
                    if ($user->role < 99) {
                        $user->tokens()->delete();
                    }
                    // if user has photo then return it else return default
                    if ($user->photo) {
                        $user->photo = env('APP_URL') . $user->photo;
                    }
                    $token_x = $user->createToken('Faveremit Password Grant Client');

                    $token = $token_x->accessToken;

                    Log::info($token_x->token->id);
                    UserDevice::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'user_agent' => $request->header('User-Agent'),
                            'device_id' => $request->header('deviceid'),
                        ],
                        [
                            'token_id' => $token_x->token->id,
                            'user_id' => $user->id,
                            'user_agent'  => $request->header('User-Agent'),
                            'device_id' => $request->header('deviceid'),
                            'ip' => $request->ip(),
                            "name" => $request->header('device'),
                            "os" => $request->header('os'),
                            "location" => $request->header('location'),
                            "isp" => $request->header('isp'),
                            "data" => json_encode($request->header()),
                        ]
                    );
                    Log::info($request->header());

                    $response = ['token' => $token, 'user' => $user];
                    return response($response, 200);
                } else {
                    $response = ["message" => "Account Deactivated. Contact support for more information"];
                    return response($response, 422);
                }
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {

            $response = [
                "error" => true,
                "message" => 'User does not exist'
            ];
            return response($response, 422);
        }
    }


    public function loginAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response(
                [
                    'error' => true,
                    'message' => $validator->errors()->all()
                ],
                422
            );
        }

        if (!str_contains($request->phone, '+')) {
            $request->phone = "+" . $request->phone;
        }

        $user = User::where('phone', $request->phone)->first();

        if ($user) {

            if ($user->status != 1) {
                $response = ["message" => "Account Deactivated. Contact support for more information"];
                return response($response, 422);
            } else {
                //if user type is not 1 or 2 return not allowed
                if ($user->role < 1) {
                    $response = [
                        'error' => true,
                        "message" => "You are not allowed to login"
                    ];
                    return response($response, 422);
                }

                if (Hash::check($request->password, $user->password)) {
                    // if user has photo then return it else return default
                    if ($user->photo) {
                        $user->photo = env('APP_URL') . $user->photo;
                    }
                    $token = $user->createToken('Faveremit Password Grant Client')->accessToken;
                    $response = [
                        'token' => $token,
                        'user' => $user,
                    ];
                    return response($response, 200);
                } else {
                    $response = ["message" => "Password isn't correct"];
                    return response($response, 422);
                }
            }
        } else {

            $response = [
                "error" => true,
                "message" => 'User does not exist'
            ];
            return response($response, 422);
        }
    }

    public function loginEmailAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response(
                [
                    'error' => true,
                    'message' => $validator->errors()->all()
                ],
                422
            );
        }



        $user = User::where('email', $request->email)->first();

        if ($user) {

            if ($user->status != 1) {
                $response = ["message" => "Account Deactivated. Contact support for more information"];
                return response($response, 422);
            } else {
                //if user type is not 1 or 2 return not allowed
                if ($user->role < 1) {
                    $response = [
                        'error' => true,
                        "message" => "You are not allowed to login"
                    ];
                    return response($response, 422);
                }

                if (Hash::check($request->password, $user->password)) {
                    // if user has photo then return it else return default
                    if ($user->photo) {
                        $user->photo = env('APP_URL') . $user->photo;
                    }
                    $token = $user->createToken('Faveremit Password Grant Client')->accessToken;
                    $response = [
                        'token' => $token,
                        'user' => $user,
                    ];
                    return response($response, 200);
                } else {
                    $response = ["message" => "Password isn't correct"];
                    return response($response, 422);
                }
            }
        } else {

            $response = [
                "error" => true,
                "message" => 'User does not exist'
            ];
            return response($response, 422);
        }
    }


    public function logout(Request $request)
    {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }

    public function test()
    {
        return response('tested', 200);
    }


    public function update(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', Rule::unique('users')->ignore($user->id)],
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }

        $user_email = User::where('email', $request->email)->first();
        if ($user_email && $user_email->id != $user->id) {
            $response = ["message" => "Email already exists"];
            return response($response, 422);
        }

        if ($request->phone) {
            if (!str_contains($request->phone, '+')) {
                $request->phone = "+" . $request->phone;
            }
        }

        $user_phone = User::where('phone', $request->phone)->first();
        if ($user_phone && $user_phone->id != $user->id) {
            $response = ["message" => "Phone already exists"];
            return response($response, 422);
        }

        if ($request->has('photo')) {
            $imageName = time() . '.' . $request->photo->extension();

            $request->photo->move(public_path(env('USER_IMG_URL') . $user->id . '/'), $imageName);
            $url = env('USER_IMG_URL') . $user->id . '/' . $imageName;

            if ($user->photo) {
                $old_image = public_path($user->photo);
                if (file_exists($old_image)) {
                    unlink($old_image);
                }
            }

            User::find($user->id)->update(['photo' => $url]);
            $user->photo = env('APP_URL') . $url;
        }



        User::find($user->id)->update($request->except(['photo']));


        $user = User::find($user->id);
        $user->photo = env('APP_URL') . $user->photo;

        $response = [
            'message' => 'Profile updated',
            'user' => $user
        ];
        return response($response, 200);
    }

    public function updatePassword(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password' => 'required|string|min:6',

        ]);
        $request['password'] = Hash::make($request['password']);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }

        $user = User::find($user->id)->update($request->toArray());
        $response = [
            'message' => "password updated"
        ];
        return response($response, 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => 'required|string|min:6',

        ]);

        $token = $request->token;
        $tokendata = DB::table('password_resets')->where('token', $token)->first();
        if ($tokendata) {

            if ($tokendata->created_at > Carbon::now()->subMinutes(60)->toDateTimeString()) {
                $user = User::where('email', $tokendata->email)->first();
                $validator = Validator::make($request->all(), [
                    'password' => 'required|string|min:6',

                ]);
                $request['password'] = Hash::make($request['password']);
                if ($validator->fails()) {
                    return response(['errors' => $validator->errors()->all()], 422);
                }
                $user = User::find($user->id)->update($request->toArray());
            } else {
                $response = [
                    'message' => "Token expired or not accepted "
                ];
                return response($response, 422);
            }
        } else {
            $response = [
                'message' => "Token expired or not accepted "
            ];
            return response($response, 422);
        }


        // $user = User::find($user->id)->update($request->toArray());
        $response = [
            'message' => "password updated"
        ];
        return response($response, 200);
    }

    public function notifyLogin(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'ip' => 'required|string|max:255',
            'os' => 'required|string|max:255',
            'device' => 'required|string|max:255',
            'time' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $details = [
            'name' => 'Oluwatobi Michael',
            'body' => 'This is for testing email using smtp'
        ];

        try {
            Mail::to($user->email)->send(new LoginNotification("$user->first_name", $request->device, $request->ip, $request->os, $request->time));
        } catch (Exception $e) {
            Log::error("Notify Login Error -> " . $e);
        }

        // dd("Email is Sent.");

        return response([
            "error" => false,
            "message" => "Email is Sent.",

        ], 200);
    }

    public function deleteAccountRequest(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $delReq = DeleteRequest::where('user_id', $user->id)->first();
        if ($delReq) {
            return response([
                'error' => true,
                'message' => 'You already have an account deletion request submitted',
            ], 373);
        }

        $deleteRequest = DeleteRequest::create([
            'user_id' => $user->id,
            'reason' => $request->reason,
        ]);



        return response([
            'error' => false,
            'message' => 'Account Deletion request submitted and would be reviewewed by Faveremit.',
            'request_details' => $deleteRequest,
        ], 200);
    }

    public function addOldAccounts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response(
                [
                    'error' => true,
                    'message' => $validator->errors()->all()
                ],
                422
            );
        }

        if ($request->password != "Pa55word") {
            return response(
                [
                    'error' => true,
                    'message' => "wrong password"
                ],
                422
            );
        }
        $headers = [
            'Content-Type: application/json',
        ];
        $response = Http::withHeaders($headers)->post("https://api.faveremit.com/api/admin/get_current_users", ["password" => $request->password]);
        Log::info($response);

        $server_output = json_decode($response);

        if ($response->status() >= 200 && $response->status() < 300) {
            foreach ($server_output->result as $user) {
                $exists_email = User::where("email", $user->email)->first();
                $exists_phone = User::where("phone", $user->phone)->first();
                if (!$exists_email) {
                    $parts = preg_split('/\s+/', $user->name);

                    $data = [
                        "first_name" => $parts[0],
                        "last_name" => count($parts) > 1 ? $parts[1] : $parts[0],
                        "phone" => $exists_phone ? "0" . $user->phone : $user->phone,
                        "email" => $user->email,
                        "username" => $user->username,
                        "photo" => $user->photo,
                        "country"  =>  $user->country,
                        "dob"  =>  $user->dob,
                        "api_token"  =>  $user->api_token,
                        "role" =>  $user->role,
                        "email_verified_at"  =>  $user->email_verified_at,
                        "phone_verified_at"  =>  $user->phone_verified_at,
                        "fcm"  =>  $user->fcm,
                        "referrer"  =>  $user->referrer,
                        "status"  =>  $user->status,
                        "password" => "faveremit_12345"
                    ];

                    $userraw = User::create($data);
                    $user = user::where('id', $userraw->id)->first();

                    $wallet =  [
                        'user_id' => $user->id,
                        'balance' => 0.0,
                    ];


                    $rewardWallet =  [
                        'user_id' => $user->id,
                        'balance' => 0.0,
                    ];

                    Wallet::create($wallet);

                    RewardWallet::create($rewardWallet);
                }
            }
        } else {
            return response(
                [
                    'error' => true,
                    'message' => "e no work"
                ],
                422
            );
        }
        return response(
            [
                'error' => false,
                'message' => "done"
            ],
            200
        );
    }
}
