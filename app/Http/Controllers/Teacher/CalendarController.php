<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalenderDayRequest;
use App\Models\CalendarHour;
use App\Models\CalenderDay;
use App\Models\LockHour;
use App\Models\ProfileTeacher;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{

    use GeneralTrait;
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
     * Store a newly created resource in storage.
     */
    public function store(CalenderDayRequest $request)
    {
        try {
            $days = $request->input('day', []);
            $hours = $request->input('hour', []);
            $teacher = auth()->user()->profile_teacher;
            foreach ($days as $ind => $day) {
                if (!$teacher->day()->where('day', $day)->first()) {
                    $newDay = $teacher->day()->create([
                        'day' => $day
                    ]);
                    $alternativeDayId = $newDay->id;
                }
                $calendarDay = $teacher->day()->where('day', $day)->first();
                foreach ($hours as $id => $hour) {
                    $key = array_keys($hour)[0];
                    $value = $hour[$key];
                    if ($ind == $key) {
                        $existingHour = CalendarHour::where('day_id', $calendarDay->id)
                            ->where('hour', $value)
                            ->first();
                        if (!$existingHour) {
                            CalendarHour::create([
                                'day_id' => isset($alternativeDayId) ? $alternativeDayId : $calendarDay->id,
                                'hour' => $value,
                                'status' => 0
                            ]);
                        }
                    }
                }
            }
            return $this->returnData(200, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
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
            // DB::commit();
            $calendar_data = $calender_day->map(function ($day) {
                return [
                    "id" => $day->id,
                    "teacher_id" => $day->teacher_id,
                    $day->day => $day->hours->map(function ($hour) {
                        return [
                            "id" => $hour->id,
                            "day_id" => $hour->day_id,
                            "status" => $hour->status,
                            "hour" => date("H:i", strtotime($hour->hour))
                        ];
                    })
                ];
            });
            return $this->returnData($calendar_data, 'operation completed successfully');
            // return $this->returnData($calender_day, 'operation completed successfully');
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
    public function update(Request $request)
    {
        try {
            $days = $request->input('day', []);
            $hours = $request->input('hour', []);
            $teacher = auth()->user()->profile_teacher;

            if (!$teacher) {
                return response()->json(['error' => 'Teacher not found'], 404);
            }

            $studentsToNotify = [];

            // التحقق من الساعات الموجودة في الجدول الأسبوعي والتي حالتها status = 1
            foreach ($days as $ind => $day) {
                $calendarDay = $teacher->day()->where('day', $day)->first();
                if ($calendarDay) {
                    $calendarHours = $calendarDay->hours()->where('status', 1)->get();
                    foreach ($calendarHours as $calendarHour) {
                        $hourFound = false;
                        foreach ($hours as $id => $hour) {
                            $key = array_keys($hour)[0];
                            $value = $hour[$key];
                            if ($ind == $key && $value == $calendarHour->hour) {
                                $hourFound = true;
                                break;
                            }
                        }
                        if (!$hourFound) {
                            return response()->json(['error' => "Hour {$calendarHour->hour} on day {$day} with status 1 is missing from request"], 400);
                        }
                    }
                }
            }

            foreach ($days as $ind => $day) {
                $calendarDay = $teacher->day()->where('day', $day)->first();
                if (!$calendarDay) {
                    $calendarDay = $teacher->day()->create([
                        'day' => $day
                    ]);
                }
                $alternativeDayId = $calendarDay->id;

                foreach ($hours as $id => $hour) {
                    $key = array_keys($hour)[0];
                    $value = $hour[$key];
                    if ($ind == $key) {
                        $existingHour = CalendarHour::where('day_id', $alternativeDayId)
                            ->where('hour', $value)
                            ->first();

                        if ($existingHour) {
                            // المحافظة على الحالة (status) إذا كانت موجودة مسبقًا
                            $existingHour->update(['status' => $existingHour->status]);
                        } else {
                            // تحديد الحالة (status) بناءً على وجود الساعة مسبقًا في الطلب أو حالتها الحالية
                            $originalHour = CalendarHour::where('day_id', $calendarDay->id)
                                ->where('hour', $value)
                                ->first();
                            $status = $originalHour && $originalHour->status == 1 ? 1 : 0;

                            CalendarHour::create([
                                'day_id' => $alternativeDayId,
                                'hour' => $value,
                                'status' => $status
                            ]);
                        }
                    }
                }

                // حذف الساعات التي لم يتم إرسالها في الطلب وحالتها 0
                $calendarHours = $calendarDay->hours;
                foreach ($calendarHours as $calendarHour) {
                    $hourFound = false;
                    foreach ($hours as $id => $hour) {
                        $key = array_keys($hour)[0];
                        $value = $hour[$key];
                        if ($ind == $key && $value == $calendarHour->hour) {
                            $hourFound = true;
                            break;
                        }
                    }
                    if (!$hourFound && $calendarHour->status == 0) {
                        // جلب جميع الطلاب الذين قدموا طلبات لحجز هذه الساعة من جدول lock_hour
                        $students = LockHour::with('student.user.wallet')
                            ->with('service')
                            ->where('hour_id', $calendarHour->id)
                            ->get();

                        foreach ($students as $student) {
                            // التحقق من نوع الخدمة
                            if ($student->service && $student->service->type == 'video call') {
                                if ($student->student && $student->student->user && $student->student->user->wallet) {
                                    $student->student->user->wallet->update([
                                        'value' => $student->student->user->wallet->value + $student->service->price
                                    ]);
                                    $student->student->user->wallet->save();
                                }
                            } else {
                                if ($student->student && $student->student->user && $student->student->user->wallet) {
                                    $student->student->user->wallet->update([
                                        'value' => $student->student->user->wallet->value + (10 / 100) * $student->service->price
                                    ]);
                                    $student->student->user->wallet->save();
                                }
                            }
                            $student->delete();
                        }

                        $studentsToNotify = array_merge($studentsToNotify, $students->toArray());
                        $calendarHour->delete();
                    }
                }
            }
            $existingDays = $teacher->day()->pluck('day');
            foreach ($existingDays as $existingDay) {
                if (!in_array($existingDay, $days)) {
                    $calendarDay = $teacher->day()->where('day', $existingDay)->first();
                    $calendarDay->hours()->delete();
                    $calendarDay->delete();
                }
            }

            DB::commit();

            // إرجاع بيانات الطلاب الذين قدموا طلبات لحجز الساعات المحذوفة
            return $this->returnData($studentsToNotify, 200, 'Operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }








    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
