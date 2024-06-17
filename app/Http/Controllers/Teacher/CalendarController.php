<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalenderDayRequest;
use App\Models\CalendarHour;
use App\Models\CalenderDay;
use App\Models\ProfileTeacher;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{

    use GeneralTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            DB::beginTransaction();
            $teacher = auth()->user()->profile_teacher;
            if (!$teacher) {
                return $this->returnError(400, 'Token is Invalid');
            }
            $calender_day = $teacher->day()->with('hours')->get();
            $calendar_data = $calender_day->map(function ($day) {
                return [
                    "id" => $day->id,
                    "teacher_id" => $day->teacher_id,
                    $day->day => $day->hours->map(function ($hour) {
                        return [
                            "id" => $hour->id,
                            "day_id" => $hour->day_id,
                            "status" => $hour->status,
                            "hour" => $hour->hour
                        ];
                    })
                ];
            });
            // DB::commit();
            return $this->returnData($calendar_data, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CalenderDayRequest $request)
    {
        try {
            DB::beginTransaction();
            $teacher = auth()->user()->profile_teacher;
            if (!$teacher) {
                return $this->returnError(400, 'Token is Invalid');
            }
            $calenderDay = $teacher->day()->where('day', $request->day)->first();
            if (!$calenderDay) {
                $day = $teacher->day()->create([
                    'day' => $request->day
                ]);
                $day->save();
                $alternativeDayId = $day->id;
            }

            $hours = $request->input('hour', []);
            foreach ($hours as $hour) {
                $calender_hour =  CalendarHour::create([
                    'day_id' => isset($calenderDay->id) ? $calenderDay->id : $alternativeDayId,
                    'status' => 0,
                    'hour' => $hour
                ]);
                $calender_hour->save();
            }
            // $calender_hour =  CalendarHour::create([
            //     'day_id' => isset($calenderDay->id) ? $calenderDay->id : $alternativeDayId,
            //     'status' => 0,
            //     'hour' => $request->hour
            // ]);
            // $calender_hour->save();
            DB::commit();
            return $this->returnData(200, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            //return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            DB::beginTransaction();
            $teacher = ProfileTeacher::find($id);
            if (!$teacher) {
                return $this->returnError(404, 'not found teacher');
            }
            $calender_day = $teacher->day()->with('hours')->get();
            DB::commit();
            return $this->returnData($calender_day, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
