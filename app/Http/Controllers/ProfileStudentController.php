<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileStudentRequest;
use App\Http\Requests\UpdateProfileStudentRequest;
use App\Models\ProfileStudent;
use Illuminate\Http\Request;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileStudentController extends Controller
{
    use GeneralTrait;


    public function getAll()
    {
        try {
            DB::beginTransaction();

            $profile_student = ProfileStudent::orderBy('created_at','desc')->get();
            if (count($profile_student) > 0)
                $profile_student->loadMissing(['user']);

            DB::commit();
            return $this->returnData($profile_student, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }


    public function store(ProfileStudentRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();

            $profile_student=ProfileStudent::firstOrNew(['user_id'=>$user->id]);
            $profile_student->educational_level = isset($request->educational_level) ? $request->educational_level : $profile_student->educational_level;
            $profile_student->phone = isset($request->phone) ? $request->phone : $profile_student->phone;
            $profile_student->save();

            $name=$request->name;
            if($name)
            {
                $user->update(['name'=>$name]);
                $profile_student->name=$name;
            }

            DB::commit();
            return $this->returnData($profile_student, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", $ex->getMessage());
        }
    }


    public function show()
    {

        try {
            DB::beginTransaction();

            $user=auth()->user();
            $user->loadMissing('profile_student');

            DB::commit();
            return $this->returnData($user, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }



    public function getById($id)
    {
        try {
            DB::beginTransaction();

            $profile_student = ProfileStudent::find($id);
            if (!$profile_student)
                return $this->returnError("404", 'Not found');
            $profile_student->loadMissing(['user']);

            DB::commit();
            return $this->returnData($profile_student, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }


    public function destroy()
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();

            $profile_student = $user->profile_student()->first();
            if (!$profile_student)
                return $this->returnError("404", 'not found');
            $profile_student->delete();

            DB::commit();
            return $this->returnSuccessMessage('operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }

    public function getByIdForAdmin($id)
    {
        try {
            DB::beginTransaction();

            $profile_student = ProfileStudent::find($id);

            if (!$profile_student)
                return $this->returnError("404", 'not found');

            $profile_student->loadMissing(['user.wallet', 'note_as_student', 'reservation_ads.ads' => function ($query) {
                $query->select('ads.id','ads.title');
            }]);
            $profile_student->loadCount([
                'report_as_reporter',
                'report_as_reported',
                'reservation_teaching_methods_free' ,
                'reservation_teaching_methods_paid'
            ]);


            DB::commit();
            return $this->returnData($profile_student, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }

}
