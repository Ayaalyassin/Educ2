<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileStudentAdsRequest;
use App\Models\Ads;
use App\Models\ProfileStudentAds;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileStudentAdsController extends Controller
{
    use GeneralTrait;


    public function getMyAds()
    {

        try {
            $profile_student=auth()->user()->profile_student()->first();
            $profile_student_ads=[];
            if($profile_student)
                $profile_student_ads=$profile_student->profile_student_ads()->get();

            return $this->returnData($profile_student_ads,'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500",$ex->getMessage());
        }
    }


    public function store(ProfileStudentAdsRequest $request)
    {
        try {
            DB::beginTransaction();
            $user=auth()->user();

            $profile_student=$user->profile_student()->first();

            $ads=Ads::find($request->ads_id);


            if(!$ads)
                return $this->returnError("404", 'Ads not found');
//            $is_exist=$profile_student->profile_student_ads()->where('ads_id',$request->ads_id)->get();
//            if(count($is_exist)>0)
//                return $this->returnError("400", 'ads already exist');
            if ($ads->date <= now()) {
                return $this->returnError("402", 'ads has begun');
            }

            if ($ads->number_students ==0) {
                return $this->returnError("401", 'The number is complete');
            }

            if ($user->wallet->value < $ads->price)
                return $this->returnError("402", 'not Enough money in wallet');
            $user->wallet->update([
                'value' => $user->wallet->value - $ads->price
            ]);
            $user->wallet->save();
            $profile_student->profile_student_ads()->attach([
                $request->ads_id
            ]);
            $ads->decrement('number_students');
            if($ads->number_students==0)
                $ads->update(['active'=>0]);
            $profile_student->loadMissing(['profile_student_ads']);

            DB::commit();
            return $this->returnData($profile_student,'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", $ex->getMessage());
        }
    }


    public function show($id)
    {
        try {
            $profile_student=auth()->user()->profile_student()->first();
            if($profile_student) {
                $profile_student_ads = $profile_student->profile_student_ads()->where('profile_student_ads.id', $id)->first();
                if (!$profile_student_ads)
                    return $this->returnError("404", 'not found');
            }
            return $this->returnData($profile_student_ads,'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500",$ex->getMessage());
        }
    }


//    public function update(UpdateProfileStudentAdsRequest $request,$id)
//    {
//        //
//    }


    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $profile_student=auth()->user()->profile_student()->first();
            if($profile_student) {
                $profile_student_ads = $profile_student->profile_student_ads()->where('profile_student_ads.id', $id)->first();
                if (!$profile_student_ads)
                    return $this->returnError("404", 'not found');
                $profile_student->profile_student_ads()->newPivotStatement()->where('id', $id)->delete();
            }

            DB::commit();
            return $this->returnSuccessMessage('operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", $ex->getMessage());
        }
    }
}
