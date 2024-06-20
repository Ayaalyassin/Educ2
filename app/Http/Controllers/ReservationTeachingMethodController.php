<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeachingMethodUserRequest;
use App\Models\ReservationTeachingMethod;
use App\Models\TeachingMethod;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationTeachingMethodController extends Controller
{
    use GeneralTrait;


    public function getMyTeachingMethod()
    {
        try {
            $profile_student=auth()->user()->profile_student()->first();
            $reservation_teaching_methods=[];
            if($profile_student) {
                $reservation_teaching_methods = $profile_student->reservation_teaching_methods()->get();
                if (count($reservation_teaching_methods) > 0)
                    $reservation_teaching_methods->loadMissing('teaching_method');
            }
            return $this->returnData($reservation_teaching_methods,'operation completed successfully');
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
            $is_exist=$profile_student->reservation_teaching_methods()->where('teaching_method_id',$request->teaching_method_id)->first();
            if($is_exist)
                return $this->returnError("400", 'teaching method already exist');

            if ($user->wallet->value < $teaching_method->price)
                return $this->returnError("402", 'not Enough money in wallet');
            $user->wallet->update([
                'value' => $user->wallet->value - $teaching_method->price
            ]);
            $user->wallet->save();

            $reservation_teaching_methods=$profile_student->reservation_teaching_methods()->create([
                'teaching_method_id'=>$request->teaching_method_id,
                'reserved_at'=>Carbon::now()->format('Y-m-d H:s:i')
            ]);

            DB::commit();
            return $this->returnData($reservation_teaching_methods,'operation completed successfully');
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
                $reservation_teaching_methods = $profile_student->reservation_teaching_methods()->where('id', $id)->first();
                if (!$reservation_teaching_methods)
                    return $this->returnError("404", 'not found');
                $reservation_teaching_methods->delete();
            }
            DB::commit();
            return $this->returnSuccessMessage('operation completed successfully');
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
                $reservation_teaching_methods = $profile_student->reservation_teaching_methods()->where('id', $id)->first();
                if (!$reservation_teaching_methods)
                    return $this->returnError("404", 'not found');
                $reservation_teaching_methods->loadMissing('teaching_method');
            }
            return $this->returnData($reservation_teaching_methods,'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500",$ex->getMessage());
        }
    }
}
