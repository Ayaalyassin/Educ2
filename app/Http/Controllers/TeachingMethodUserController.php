<?php

namespace App\Http\Controllers;

use App\Models\TeachingMethodUser;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\TeachingMethodUserRequest;
use App\Models\TeachingMethod;

class TeachingMethodUserController extends Controller
{
    use GeneralTrait;


    public function getMyTeachingMethod()
    {
        try {
            $profile_student=auth()->user()->profile_student()->first();
            $teaching_methods_user=[];
            if($profile_student)
                $teaching_methods_user=$profile_student->teaching_methods_user()->get();
            return $this->returnData($teaching_methods_user,'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500",$ex->getMessage());
        }
    }



    public function store(TeachingMethodUserRequest $request)
    {
        try {
            DB::beginTransaction();

            $user=auth()->user();

            $profile_student=$user->profile_student()->first();

            $teaching_method=TeachingMethod::find($request->teaching_method_id);

            if(!$teaching_method)
                return $this->returnError("404", 'teaching method not found');
            $is_exist=$profile_student->teaching_methods_user()->where('teaching_method_id',$request->teaching_method_id)->get();
            if(count($is_exist)>0)
                return $this->returnError("400", 'teaching method already exist');

            if ($user->wallet->value < $teaching_method->price)
                return $this->returnError("402", 'not Enough money in wallet');
            $user->wallet->update([
                'value' => $user->wallet->value - $teaching_method->price
            ]);
            $user->wallet->save();

            $profile_student->teaching_methods_user()->attach([
                $request->teaching_method_id
            ]);
            $profile_student->loadMissing(['teaching_methods_user']);

            DB::commit();
            return $this->returnData($profile_student,'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", $ex->getMessage());
        }
    }


    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $profile_student=auth()->user()->profile_student()->first();
            if($profile_student) {
                $teaching_method_user = $profile_student->teaching_methods_user()->where('teaching_method_users.id', $id)->first();
                if (!$teaching_method_user)
                    return $this->returnError("404", 'not found');
                $profile_student->teaching_methods_user()->newPivotStatement()->where('id', $id)->delete();
            }
            DB::commit();
            return $this->returnSuccessMessage('operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", $ex->getMessage());
        }
    }
}
