<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeriesRequest;
use App\Http\Requests\UpdateSeriesRequest;
use App\Models\ProfileTeacher;
use App\Models\Series;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeriesController extends Controller
{
    use GeneralTrait;
    private $uploadPath = "assets/images/series";

    public function index($teacher_id)
    {
        try {
            $profile_teacher=ProfileTeacher::find($teacher_id);
            if (!$profile_teacher) {
                return $this->returnError("404",'Profile Teacher Not found');
            }
            $teaching_methods=$profile_teacher->teaching_methods()->whereHas('series')->orderBy('created_at','desc')->get();
            $teaching_methods->loadMissing('series');
            return $this->returnData($teaching_methods, __('backend.operation completed successfully', [], app()->getLocale()));
        } catch (\Exception $ex) {
            return $this->returnError("500",$ex->getMessage());
        }
    }



    public function store(SeriesRequest $request)
    {
        try {
            DB::beginTransaction();

            $profile_teacher=auth()->user()->profile_teacher()->first();
            $teaching_method=$profile_teacher->teaching_methods()->find($request->teaching_method_id);
            if (!$teaching_method)
                return $this->returnError("404",'teaching_method Not found');

            $series = $request->series;
            $list_series = [];
            foreach ($series as $value) {
                $file = $this->saveImage($value['file'], $this->uploadPath);
                $data = [
                    'teaching_method_id' => $teaching_method->id,
                    'file' => $file,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                array_push($list_series, $data);
            }
            Series::insert($list_series);


            DB::commit();
            return $this->returnData($list_series, __('backend.operation completed successfully', [], app()->getLocale()));
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", $ex->getMessage());
        }
    }


    public function show($id)
    {
        try {
            DB::beginTransaction();

            $profile_teacher=auth()->user()->profile_teacher()->first();
            $teaching_methods_ids=$profile_teacher->teaching_methods()->get('id');

            $series= Series::whereIn('teaching_method_id',$teaching_methods_ids)->first();
            if (!$series) {
                return $this->returnError("404",'series Not found');
            }

            DB::commit();
            return $this->returnData($series, __('backend.operation completed successfully', [], app()->getLocale()));
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }


    public function update(UpdateSeriesRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $profile_teacher=auth()->user()->profile_teacher()->first();

            $series = Series::where('id', $id)
                ->whereHas('teaching_method', function ($query) use ($profile_teacher) {
                    $query->where('teaching_methods.profile_teacher_id', $profile_teacher->id);
                })
                ->first();

            if (!$series)
                return $this->returnError("404",'series Not found');

            $file=null;
            if (isset($request->file)) {
                $this->deleteImage($series->file);
                $file = $this->saveImage($request->file, $this->uploadPath);
            }

            $series->update([
                'file'=>isset($request->file)? $file :$series->file,
            ]);


            DB::commit();
            return $this->returnData($series, __('backend.operation completed successfully', [], app()->getLocale()));
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
            $series=Series::where('id',$id)->whereHas('teaching_method',function ($query)use($profile_teacher)
            {
                $query->where('teaching_methods.profile_teacher_id',$profile_teacher);
            })->first();
            if (!$series)
                return $this->returnError("404",'series Not found');


            if (isset($series->file)) {
                $this->deleteImage($series->file);
            }

            $series->delete();

            DB::commit();
            return $this->returnSuccessMessage(__('backend.operation completed successfully', [], app()->getLocale()));
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }
    public function getMySeries()
    {
        try {
            $profile_teacher =auth()->user()->profile_teacher()->first();
            $series=[];
            if($profile_teacher) {
                $series = $profile_teacher->teaching_methods()->whereHas('series')->orderBy('created_at', 'desc')->get();
                $series->loadMissing('series');
            }

            return $this->returnData($series, __('backend.operation completed successfully', [], app()->getLocale()));
        } catch (\Exception $ex) {
            return $this->returnError("500", "Please try again later");
        }
    }
}
