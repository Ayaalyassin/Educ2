<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileStudentAdsRequest;
use App\Models\Ads;
use App\Models\ReservationAds;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationAdsController extends Controller
{
    use GeneralTrait;


    public function getMyAds()
    {

        try {
            $profile_student=auth()->user()->profile_student()->first();
            $reservation_ads=[];
            if($profile_student) {
                $reservation_ads = $profile_student->reservation_ads()->get();
                if (count($reservation_ads) > 0)
                    $reservation_ads->loadMissing('ads');
            }

            return $this->returnData($reservation_ads,'operation completed successfully');
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
            $is_exist=$profile_student->reservation_ads()->where('ads_id',$request->ads_id)->first();
            if($is_exist)
                return $this->returnError("400", 'ads already exist');
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
            $reservation_ads=$profile_student->reservation_ads()->create([
                'ads_id'=>$request->ads_id,
                'reserved_at'=>Carbon::now()->format('Y-m-d H:i:s')
            ]);
            $ads->decrement('number_students');
            if($ads->number_students==0)
                $ads->update(['active'=>0]);

            DB::commit();
            return $this->returnData($reservation_ads,'operation completed successfully');
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
                $reservation_ads = $profile_student->reservation_ads()->where('id', $id)->first();
                if (!$reservation_ads)
                    return $this->returnError("404", 'not found');
                $reservation_ads->loadMissing('ads');
            }
            return $this->returnData($reservation_ads,'operation completed successfully');
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
                $reservation_ads = $profile_student->reservation_ads()->where('id', $id)->first();
                if (!$reservation_ads)
                    return $this->returnError("404", 'not found');
                $reservation_ads->delete();
            }

            DB::commit();
            return $this->returnSuccessMessage('operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", $ex->getMessage());
        }
    }
}
