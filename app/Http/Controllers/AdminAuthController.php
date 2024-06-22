<?php

namespace App\Http\Controllers;

use App\Http\Requests\CodeRequest;
use App\Http\Requests\LoginRequest;
use App\Jobs\DeleteCodeJob;
use App\Jobs\sendCodeEmailJob;
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
            return $this->returnError(401,'The password is wrong');

        if (!$token)
            return $this->returnError(401, 'Account Not found');
        $is_block=$exist->whereHas('block')->first();
        if($is_block)
            return $this->returnError(401,'You are block');

        $code=mt_rand(100000, 999999);
        $exist->update([
            'code' => $code,
        ]);
        $mailData = [

            'title' => 'Code login',

            'code' => $code

        ];
//        Mail::to($exist->email)->send(new CodeEmail($mailData));
//        sendCodeEmailJob::dispatch($mailData,$exist)->delay(Carbon::now()->addSeconds(2));
//        DeleteCodeJob::dispatch($exist)->delay(Carbon::now()->addMinutes(2));

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

}
