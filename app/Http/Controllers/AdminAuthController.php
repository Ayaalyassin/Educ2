<?php

namespace App\Http\Controllers;

use App\Http\Requests\CodeRequest;
use App\Http\Requests\LoginRequest;
use App\Jobs\DeleteCodeJob;
use App\Models\User;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Mail;
use App\Mail\CodeEmail;


class AdminAuthController extends Controller
{
    use GeneralTrait;

    public function login_admin(LoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);
        $token = JWTAuth::attempt($credentials);
        $exist=User::where('email',$request->email)->first();
        if($exist && !$token)
            return $this->returnError(401,__('backend.The password is wrong', [], app()->getLocale()));

        if (!$token)
            return $this->returnError(401,__('backend.Account Not found', [], app()->getLocale()));
        $is_block=User::where('email',$request->email)->whereHas('block')->first();

        if($is_block)
            return $this->returnError(401,__('backend.You are block', [], app()->getLocale()));

        $code=mt_rand(100000, 999999);
        $exist->update([
            'code' => $code,
        ]);
        $mailData = [

            'title' => 'Code login',

            'code' => $code,

        ];

        //Mail::to($exist->email)->send(new CodeEmail($mailData));
        //sendCodeEmailJob::dispatch($mailData,$exist)->delay(Carbon::now()->addSeconds(2));
        //DeleteCodeJob::dispatch($exist)->delay(Carbon::now()->addMinutes(6));
        return $this->returnSuccessMessage(__('backend.code send successfully', [], app()->getLocale()));
    }


    public function codeAdmin(CodeRequest $request)
    {
        try {
            $code = $request->code;

            $user = User::where('email', $request->email)->first();
            if (!$user)
                return $this->returnError('404', __('backend.The Email Not Found', [], app()->getLocale()));

            if (!$user->code)
                return $this->returnError("401", __('backend.Please request the code again', [], app()->getLocale()));

            if ($user->code != $code)
                return $this->returnError("400", __('backend.The entered verification code is incorrect', [], app()->getLocale()));

            $token = JWTAuth::fromUser($user);
            if (!$token) return $this->returnError('402', 'Unauthorized');
            $user->token=$token;
            $user->loadMissing(['roles']);

            return $this->returnData($user, __('backend.operation completed successfully', [], app()->getLocale()));


        } catch (\Exception $ex) {
            return $this->returnError("500", 'Please try again later');
        }

    }

    public function test()
    {
        $exist=auth()->user();
        $code=mt_rand(100000, 999999);
        $exist->update([
            'code' => $code,
        ]);
        $mailData = [

            'title' => 'Code login',

            'code' => $code

        ];
        Mail::to($exist->email)->send(new CodeEmail($mailData));
        DeleteCodeJob::dispatch($exist)->delay(Carbon::now()->addMinutes(2));
    }

}
