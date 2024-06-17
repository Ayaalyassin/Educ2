<?php

namespace App\Http\Controllers;

use App\Models\ProfileStudent;
use App\Models\ProfileTeacher;
use App\Models\Report;
use Illuminate\Http\Request;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ReportRequest;
use Carbon\Carbon;
use App\Models\User;

class ReportController extends Controller
{
    use GeneralTrait;

    public function index()
    {
        try {
            DB::beginTransaction();
            $reports = Report::with(['reporter' => function ($q) {
                //$q->select('id', 'name');

            }])->with(['reported' => function ($q) {
                //$q->select('id', 'name');
            }])->orderBy('created_at','desc')->get();

            DB::commit();
            return $this->returnData($reports,'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", $ex->getMessage());
        }
    }


    public function report_student(ReportRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user()->profile_teacher()->first();

            $profile_student = ProfileStudent::find($request->reported_id);
            if (!$profile_student) {
                return $this->returnError("404", 'Not found' . ' Profile student Id : ' . $request->reported_id);

            }

            $report = $user->report_as_reporter()->where('reported_id', $request->reported_id)->first();
            if ($report) {
                $user->report_as_reporter()->update([
                    'reason' => $request->reason,
                    'date'=>Carbon::now()->format('Y-m-d H:i:s')
                ]);
            }
            else {
                $user->report_as_reporter()->create([
                    'reason' => $request->reason,
                    'reported_id' => $request->reported_id,
                    'reported_type' => "App\Models\ProfileStudent",
                    'date'=>Carbon::now()->format('Y-m-d H:i:s')
                ]);
            }
            $profile_student->loadMissing(['report_as_reported']);

            DB::commit();
            return $this->returnData($profile_student, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", $ex->getMessage());
        }
    }



    public function report_teacher(ReportRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user()->profile_student()->first();

            $reported_id=$request->reported_id;
            $profile_teacher = ProfileTeacher::find($reported_id);
            if (!$profile_teacher) {
                return $this->returnError("404",'Not found' . ' Profile Teacher Id : ' . $reported_id);
            }

            $services_ids=$profile_teacher->service_teachers()->pluck('id');

            $is_lock=null;

            if($services_ids) {
                $is_lock = $user->whereHas('hour_lock', function ($query) use ($services_ids) {
                    $query->whereIn('service_id', $services_ids);
                })->first();
            }

            if(!$is_lock)
                return $this->returnError("403",'You Can’t do it');


            $report = $user->report_as_reporter()->where('reported_id', $reported_id)->first();
            if ($report) {
                $report = $user->report_as_reporter()->update([
                    'reason' => $request->reason,
                    'date'=>Carbon::now()->format('Y-m-d H:i:s')
                ]);
            }
            else {
                $report = $user->report_as_reporter()->create([
                    'reason' => $request->reason,
                    'reported_id' => $reported_id,
                    'reported_type' => "App\Models\ProfileTeacher",
                    'date'=>Carbon::now()->format('Y-m-d H:i:s')
                ]);
            }

            $profile_teacher->loadMissing('report_as_reported');

            DB::commit();
            return $this->returnData($profile_teacher, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", $ex->getMessage());
        }
    }



}
