<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAdsRequest;
use App\Models\Ads;
use App\Models\ProfileTeacher;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\AdsRequest;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;

class AdsController extends Controller
{
    use GeneralTrait;

    private $uploadPath = "assets/images/ads";

    public function getSimilar()
    {
        $user=auth()->user();
        $desiredData=Ads::whereRaw("INSTR(place, ?) > 0", [$user->governorate])->get();

        return $this->returnData($desiredData, 'operation completed successfully');
    }

    public function index()
    {
        try {
            //$ads = Ads::all();
            $ads=Ads::join('profile_teachers','ads.profile_teacher_id','=','profile_teachers.id')->
            join('users','profile_teachers.user_id','=','users.id')
            ->select('ads.*','users.name')//->orderBy('created_at','desc')
            ->get();

            return $this->returnData($ads, 'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500", "Please try again later");
        }
    }

    public function getAdsTeacher($teacherId)
    {
        try {
//            $user = User::find($userId);
//            $profile_teacher=$user->profile_teacher()->first();
//            $ads=$profile_teacher->ads()->get();
            $profile_teacher = ProfileTeacher::find($teacherId);
            if(!$profile_teacher)
                return $this->returnError("404", "Not found");
            $ads=$profile_teacher->ads()->orderBy('created_at','desc')->get();


            return $this->returnData($ads, 'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500", "Please try again later");
        }
    }


    public function store(AdsRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = auth()->user();

            $file = null;
            if (isset($request->file)) {
                $file = $this->saveImage($request->file, $this->uploadPath);
            }
            $date=new \DateTime($request->date);

            $profile_teacher=$user->profile_teacher()->first();
            $ads =$profile_teacher->ads()->create([
                'title' => $request->title,
                'description' => $request->description,
                'price' => $request->price,
                'number_students' => $request->number_students,
                'file' => $file,
                'place'=>$request->place,
                'date'=>$date,
            ]);


            DB::commit();
            return $this->returnData($ads, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", $ex->getMessage());
        }
    }



    public function getById($id)
    {
        try {
            $data = Ads::where('id', $id)
//->with('profile_students',function ($query){
//                $query->select('phone');
//            })->with('profile_students.user',function ($q){
//                $q->select('name','governorate');
//            })
                ->first();
            if (!$data) {
                return $this->returnError("404", "Not found");
            }
            $data->loadMissing(['profile_students.user']);
            return $this->returnData($data, 'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500", 'Please try again later');
        }
    }



    public function update(UpdateAdsRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $user = auth()->user();

            $profile_teacher = $user->profile_teacher()->first();
            $ads=$profile_teacher->ads()->find($id);

            if (!$ads)
                return $this->returnError("404", 'not found');

            $file = null;
            if (isset($request->file)) {
                $this->deleteImage($ads->file);
                $file = $this->saveImage($request->file, $this->uploadPath);
            }
            $ads->update([
                'title' => isset($request->title) ? $request->title : $ads->title,
                'description' => isset($request->description) ? $request->description : $ads->description,
                'price' => isset($request->price) ? $request->price : $ads->price,
                'number_students' => isset($request->number_students) ? $request->number_students : $ads->number_students,
                'file' => isset($request->file) ? $file : $ads->file,
                'place'=> isset($request->place) ? $request->place : $ads->place,
                'date'=> isset($request->date) ? new \DateTime($request->date) : $ads->date,
            ]);

            DB::commit();
            return $this->returnData($ads, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }



    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $profile_teacher = auth()->user()->profile_teacher()->first();

            $ads = $profile_teacher->ads()->find($id);
            if (!$ads)
                return $this->returnError("404", 'not found');
            if (isset($ads->file)) {
                $this->deleteImage($ads->file);
            }

            $ads->delete();
            DB::commit();
            return $this->returnSuccessMessage('operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }

    public function getMyAds()
    {
        try {
            $profile_teacher =auth()->user()->profile_teacher()->first();

            $ads=[];
            if($profile_teacher)
                $ads=$profile_teacher->ads()->get();

            return $this->returnData($ads, 'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500", "Please try again later");
        }
    }
}
