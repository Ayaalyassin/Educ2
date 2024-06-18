<?php

namespace App\Http\Controllers;

use App\Http\Requests\CodeRequest;
use App\Http\Requests\EmailRequest;
use App\Http\Requests\PasswordNewRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Jobs\ForgetPasswordJob;
use App\Models\User;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class PasswordController extends Controller
{
    use GeneralTrait;

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
                //$code=mt_rand(100000, 999999);
                $user->update([
                    'code' => $code,
                ]);
                $mailData = [
                    'title' => 'Forget Password Email',
                    'code' => $code
                ];
//                $job=(new ForgetPasswordJob($mailData,$user))->delay(Carbon::now()->addSeconds(5));
//                $this->dispatch($job);
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
            if (!$user->code)
                return $this->returnError("401", 'Please request the code again');

            if ($user->code != $code)
                return $this->returnError("400", 'The entered verification code is incorrect');

            return $this->returnSuccessMessage('operation completed successfully');


        } catch (\Exception $ex) {
            return $this->returnError("500", 'Please try again later');
        }
    }


    public function passwordNew(PasswordNewRequest $request)
    {
        try {

            $user = User::where('email', $request->email)->first();
            if (!$user)
                return $this->returnError('404', 'The Email Not Found');

            $user->update([
                'password' => $request->password,
            ]);

            $token = JWTAuth::fromUser($user);
            if (!$token) return $this->returnError('401', 'Unauthorized');

            $user->token = $token;
            return $this->returnData($user, 'Logged in successfully');

        } catch (\Exception $ex) {
            return $this->returnError("500", 'Please try again later');
        }
    }
}
