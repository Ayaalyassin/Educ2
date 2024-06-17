<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTeachingMethodRequest;
use App\Models\ProfileTeacher;
use App\Models\TeachingMethod;
use Illuminate\Http\Request;
use App\Http\Requests\TeachingMethodRequest;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;

class TeachingMethodController extends Controller
{
    use GeneralTrait;

    private $uploadPath = "assets/images/teaching_methods";


    public function index($teacher_id)
    {
        try {
            //$profile_teacher=User::find($teacher_id)->profile_teacher()->first();
            $profile_teacher=ProfileTeacher::find($teacher_id);
            if (!$profile_teacher) {
                return $this->returnError("404",'Profile Teacher Not found');
            }
            $teaching_methods=$profile_teacher->teaching_methods()->get();
            return $this->returnData($teaching_methods,'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500",$ex->getMessage());
        }
    }



    public function show($id)
    {
        try {
            DB::beginTransaction();

            $teaching_method= TeachingMethod::find($id);
            if (!$teaching_method) {
                return $this->returnError("404",'teaching_method Not found');
            }
            $teaching_method->increment('views');

            DB::commit();
            return $this->returnData($teaching_method,'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }



    public function store(TeachingMethodRequest $request)
    {
        try {
            DB::beginTransaction();
            $profile_teacher=auth()->user()->profile_teacher()->first();

            $file = $this->saveImage($request->file, $this->uploadPath);

            $teaching_method= $profile_teacher->teaching_methods()->create([
                'title'=>$request->title,
                'type'=>$request->type,
                'description'=>$request->description,
                'file'=>$file,
                'status'=>$request->status,
                'price'=>$request->price
            ]);


            DB::commit();
            return $this->returnData($teaching_method,'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", $ex->getMessage());
        }
    }




    public function update(UpdateTeachingMethodRequest $request,$id)
    {
        try {
            DB::beginTransaction();

            $profile_teacher=auth()->user()->profile_teacher()->first();

            $teaching_method=$profile_teacher->teaching_methods()->find($id);

            $file=null;
            if (isset($request->file)) {
                $this->deleteImage($teaching_method->file);
                $file = $this->saveImage($request->file, $this->uploadPath);
            }

            $teaching_method->update([
                'title'=>isset($request->title)? $request->title :$teaching_method->title,
                'type'=>isset($request->type)? $request->type :$teaching_method->type,
                'description'=>isset($request->description)? $request->description :$teaching_method->description,
                'file'=>isset($request->file)? $file :$teaching_method->file,
                'status'=>isset($request->status)? $request->status :$teaching_method->status,
                'price'=>isset($request->price)? $request->price :$teaching_method->price,
            ]);

            DB::commit();
            return $this->returnData($teaching_method,'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }


    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $profile_teacher=auth()->user()->profile_teacher()->first();
            $teaching_method=$profile_teacher->teaching_methods()->find($id);

            if (!$teaching_method) {
                return $this->returnError("404",'teaching_method Not found');
            }
            if (isset($teaching_method->file)) {
                $this->deleteImage($teaching_method->file);
            }

            $teaching_method->delete();

            DB::commit();
            return $this->returnSuccessMessage('operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }

    public function getMyTeachingMethod()
    {
        try {
            $profile_teacher =auth()->user()->profile_teacher()->first();
            $teaching_methods=[];
            if($profile_teacher)
                $teaching_methods=$profile_teacher->teaching_methods()->get();

            return $this->returnData($teaching_methods, 'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500", "Please try again later");
        }
    }
}
