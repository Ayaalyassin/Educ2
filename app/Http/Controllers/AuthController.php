<?php

namespace App\Http\Controllers;

use App\Http\Requests\CodeRequest;
use App\Http\Requests\EmailRequest;
use App\Http\Requests\PasswordNewRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Jobs\SendFcmNotification;
use App\Mail\CodeEmail;
use App\Mail\ForgetPasswordMail;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    use GeneralTrait;

    private $uploadPath = "assets/images/users";


    public function login(LoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);
        $token = JWTAuth::attempt($credentials);

        $exist=User::where('email',$request->email)->first();
        if($exist && !$token)
            return $this->returnError(401,'The password is wrong');

        if (!$token)
            return $this->returnError(401,'Account Not found');

        $user = auth()->user();
        $user->token = $token;
        $user->loadMissing(['roles']);

        return $this->returnData($user, 'operation completed successfully');
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }


    public function handleGoogleCallback()
    {
        $user = Socialite::driver('google')->user();

        $existingUser = User::where('google_id', $user->id)->first();

        if ($existingUser) {
            auth()->login($existingUser, true);
        } else {
            $newUser = new User();
            $newUser->name = $user->name;
            $newUser->email = $user->email;
            $newUser->google_id = $user->id;
            $newUser->password = bcrypt(request(Str::random()));
            $newUser->save();

            auth()->login($newUser, true);
        }

        return $this->returnData($user, 'operation completed successfully');
    }



    public function login_admin(LoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);
        $token = JWTAuth::attempt($credentials);
        $exist=User::where('email',$request->email)->first();
        if($exist && !$token)
            return $this->returnError(401,'The password is wrong');

        if (!$token)
            return $this->returnError(401, 'Account Not found');

        $code=mt_rand(1, 999999);
        $exist->update([
            'code' => $code,
        ]);
        $mailData = [

            'title' => 'Code login',

            'code' => $code

        ];
        //Mail::to($exist->email)->send(new CodeEmail($mailData));

        return $this->returnSuccessMessage('code send successfully');
    }


    public function codeAdmin(CodeRequest $request)
    {
        try {
            $code = $request->code;

            $user = User::where('email', $request->email)->first();
            if (!$user)
                return $this->returnError('402', 'The Email Not Found');

            if (!$user->code)
                return $this->returnError("401", 'Please request the code again');

            if ($user->code != $code)
                return $this->returnError("403", 'The entered verification code is incorrect');

                $token = JWTAuth::fromUser($user);
                if (!$token) return $this->returnError('402', 'Unauthorized');
                $user->token=$token;

            return $this->returnData($user, 'operation completed successfully');


        } catch (\Exception $ex) {
            return $this->returnError("500", 'Please try again later');
        }

    }

    public function register(RegisterRequest $request)
    {

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'password'       => $request->password,
            'address'         => $request->address,
            'governorate'    => $request->governorate,
            'birth_date'     => $request->birth_date
        ]);

        $credentials = ['email' => $user->email, 'password' => $request->password];
        $token = JWTAuth::attempt($credentials);
        $user->token = $token;

        $role = Role::where('id', '=', $request->role_id)->first();
        if(!$role)
            return $this->returnError(404,'Role Not found');
        $user->assignRole($role);
        $user->loadMissing(['roles']);
        if (!$token)
            return $this->returnError(401, 'Unauthorized');
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'number' => random_int(1000000000000, 9000000000000),
            'value' => 0,
        ]);
        return $this->returnData($user, 'operation completed successfully');
    }


    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        if ($token) {
            try {
                JWTAuth::setToken($token)->invalidate();
                return $this->returnSuccessMessage("Logged out successfully", "200");
            } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
                return $this->returnError($e->getCode(), 'some thing went wrongs');
            }
        } else {
            return $this->returnError("400", 'some thing went wrongs');
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $user = auth()->user();
            if (Hash::check($request->old_password, $user->password)) {
                $user->update([
                    'password' => $request->password,
                ]);
                return $this->returnSuccessMessage('operation completed successfully');
            } else {
                return $this->returnError("400",'failed');
            }
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(),'Please try again later');
        }
    }


    public function forgetPassword(EmailRequest $request)
    {
        try {
            $user =User::where('email',$request->email)->first();
            if($user) {
                $code = mt_rand(1000, 9999);
                //$code=mt_rand(0, 999999);
                $user->update([
                    'code' => $code,
                ]);
                $mailData = [
                    'title' => 'Forget Password Email',
                    'code' => $code
                ];
                //Mail::to($user->email)->send(new ForgetPasswordMail($mailData));
                return $this->returnSuccessMessage('operation completed successfully');
            }
            else
            {
                return $this->returnError("404", 'The Email Not Found');
            }

        } catch (\Exception $ex) {
            return $this->returnError("500",'Please try again later');
        }
    }


    public function checkCode(CodeRequest $request)
    {
        try {
            $code = $request->code;

            $user = User::where('email',$request->email)->first();
            if(!$user)
                return $this->returnError('404', 'The Email Not Found');
            //if (isset($user)) {
                if (!$user->code)
                    return $this->returnError("401", 'Please request the code again');

                if ($user->code != $code)
                    return $this->returnError("400", 'The entered verification code is incorrect');

//                $token = JWTAuth::fromUser($user);
//                if (!$token) return $this->returnError('402', 'Unauthorized');

//                $user = $this->userWithToken($user, $token);
            return $this->returnSuccessMessage('operation completed successfully');

            //} else return $this->returnError('405', 'Please try again later');

        } catch (\Exception $ex) {
            return $this->returnError("500", 'Please try again later');
        }
    }

    private function userWithToken($user,$token)
    {
        $user->access_token = $token;
        $user->token_type =  'bearer';
        $user->expires_in = JWTAuth::factory()->getTTL() ;
        return $user;
    }

    public function passwordNew(PasswordNewRequest $request)
    {
        try {

            $user = User::where('email', $request->email)->first();
            if (!$user)
                return $this->returnError('404', 'The Email Not Found');
            if (isset($user)) {
                $user->update([
                    'password' => $request->password,
                ]);

                $token = JWTAuth::fromUser($user);
                if (!$token) return $this->returnError('401', 'Unauthorized');

                //$user = $this->userWithToken($user, $token);
                $user->token=$token;
                return $this->returnData($user, 'Logged in successfully');

            } else return $this->returnError('405', 'Please try again later');

        } catch (\Exception $ex) {
            return $this->returnError("500", 'Please try again later');
        }
    }

    public function deleteMyAccount()
    {
        try {
            $user = auth()->user();
            if($user->image)
                $this->deleteImage($user->image);
            $user->delete();

        } catch (\Exception $ex) {
            return $this->returnError("500",'Please try again later');
        }
    }

    public function refreshToken(Request $request)
    {
        try {
            $user_id = $request->user_id;
            $fcm_token = $request->fcm_token;
            $user = User::find($user_id);
            if(!$user)
                return $this->returnError('404', 'Not found');
            $user->update([
                'fcm_token' => $fcm_token
            ]);
            return $this->returnData($user, 'operation completed successfully');
        }
        catch (\Exception $e)
        {
            return $this->returnError("500",'Please try again later');
        }
    }

    public function test()
    {
        $user=auth()->user();
        //dispatch(new SendFcmNotification($user->id,"message","title"));
        $SERVER_KEY=env('FCM_SERVER_KEY');
        $fcm=Http::acceptJson()->withToken($SERVER_KEY)
            ->post('https://fcm.googleapis.com/fcm/send',
                [
                    'to'=>$user->fcm_token,
                    'notification'=>
                        [
                            'title'=>"title",
                            'body'=>"message"
                        ]
                ]);
        return $fcm;
        //return $this->returnSuccessMessage('operation completed successfully');
    }

}
