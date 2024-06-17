<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\LockHourRequest;
use App\Models\CalendarHour;
use App\Models\LockHour;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LockHourController extends Controller
{
    use GeneralTrait;


    public function index()
    {
        try {
            $user = Auth::user();
            $teacher = $user->profile_teacher;
            if (!$teacher) {
                return $this->returnError(400, 'Token is Invalid');
            }
            $lock_hour = DB::table('calendar_days')
                ->where('calendar_days.teacher_id', '=', $teacher->id)
                ->join('calendar_hours', 'calendar_days.id', '=', 'calendar_hours.day_id')
                ->join('lock_hours', 'calendar_hours.id', '=', 'lock_hours.hour_id')
                ->where('lock_hours.status', '=', 0)
                ->join('profile_students', 'profile_students.id', '=', 'lock_hours.student_id')
                ->join('users', 'users.id', '=', 'profile_students.user_id')
                ->select(
                    'users.name',
                    'users.address',
                    'users.governorate',
                    'calendar_days.day',
                    'calendar_hours.hour',
                )
                ->get();
            return $this->returnData($lock_hour, 'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function store(LockHourRequest $request)
    {
        try {
            $user = Auth::user();
            $student = $user->profile_student;
            $wallet = $user->wallet;

            if (!$student) {
                return $this->returnError(400, 'Token is Invalid');
            }

            $locks = $student->hour_lock;
            foreach ($locks as $lock) {
                if (
                    $lock->service_id == $request->service_id and
                    $lock->hour_id == $request->hour_id
                ) {
                    return $this->returnError(400, 'already request hour lock');
                }
            }


            // return $this->returnData($student->hour_lock, 'operation completed successfully');

            $hours = CalendarHour::find($request->hour_id)->day->teacher->service_teachers;

            if (!CalendarHour::find($request->hour_id)) {
                return $this->returnError(404, 'The Hour Not Found');
            }

            foreach ($hours as $hour) {
                if ($hour->id == $request->service_id) {
                    if ($wallet->value < $hour->price) {
                        return $this->returnError(501, 'not Enough money in wallet');
                    }
                    if ($hour->type == 'private lesson') {
                        $wallet->update([
                            'value' => $wallet->value  - (10 / 100) * $hour->price,
                        ]);
                    } elseif ($hour->type == 'video call') {
                        $wallet->update([
                            'value' => $wallet->value  - $hour->price,
                        ]);
                    }
                    $student->hour_lock()->create([
                        'hour_id' => $request->hour_id,
                        'service_id' => $request->service_id,
                        'status' => 0
                    ]);
                    return $this->returnData(200, 'operation completed successfully');
                }
            }

            return $this->returnError(404, 'The service Not Found');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    
    public function destroy($id)
    {
        try {
            $lockHour = LockHour::find($id);
            $teacher = auth()->user()->profile_teacher;
            if (!$lockHour) {
                return $this->returnError(400, 'not found request');
            }
            if ($lockHour->status == 1) {
                return $this->returnError(400, "can't delete because the request is accept");
            }
            if (!$teacher) {
                return $this->returnError(400, 'Token is Invalid');
            }
            $user = $lockHour->student->user->wallet;
            $wallet = $user->update([
                'value' => $user->value + (10 / 100) * $lockHour->service->price,
            ]);
            $lockHour->delete();
            return $this->returnData($wallet, 'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function get_my_request()
    {

        try {
            $user = Auth::user()->profile_student;
            if (!$user) {
                return $this->returnError(400, 'Token is Invalid');
            }
            $lock_hour = DB::table('lock_hours')
                ->where('lock_hours.student_id', '=', $user->id)
                ->join('service_teachers', 'service_teachers.id', '=', 'lock_hours.service_id')
                ->join('calendar_hours', 'calendar_hours.id', '=', 'lock_hours.hour_id')
                ->join('calendar_days', 'calendar_days.id', '=', 'calendar_hours.day_id')
                ->join('profile_teachers', 'profile_teachers.id', '=', 'calendar_days.teacher_id')
                ->join('users', 'users.id', '=', 'profile_teachers.user_id')
                ->select(
                    'service_teachers.type',
                    'calendar_hours.hour',
                    'calendar_days.day',
                    'users.name',
                    'users.address',
                    'users.governorate'
                )
                ->get();
            return $this->returnData($lock_hour, 'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function accept_request($id)
    {

        try {
            $user = Auth::user()->profile_teacher;
            if (!$user) {
                return $this->returnError(400, 'Token is Invalid');
            }

            $lock_hour = LockHour::find($id);
            if (!$lock_hour) {
                return $this->returnError(404, 'Not found Request');
            }
            $wallet = Auth::user()->wallet;

            if ($lock_hour->service->type == 'video call') {
                $wallet->update([
                    'value' => $wallet->value + $lock_hour->service->price
                ]);
            } elseif ($lock_hour->service->type == 'private lesson') {
                $wallet->update([
                    'value' => $wallet->value + (10 / 100) * $lock_hour->service->price
                ]);
            }
            $hour = $lock_hour->hour;
            $deleteHours = $hour->hour_lock;
            foreach ($deleteHours as $deleteHour) {
                if ($deleteHour->id == $id) {
                    $deleteHour->update([
                        'status' => 1
                    ]);
                } else {
                    $deleteHour->delete();
                }
            }
            return $this->returnData(200, 'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function delete_my_request($id)
    {
        try {
            $user = Auth::user()->profile_student;
            $wallet = Auth::user()->wallet;
            if (!$user) {
                return $this->returnError(400, 'Token is Invalid');
            }
            $lock_hour = LockHour::find($id);
            if (!$lock_hour) {
                return $this->returnError(404, 'Not found Request');
            }
            if ($lock_hour->status == 1) {
                return $this->returnError(500, "can't delete Request");
            }
            if ($lock_hour->service->type == 'video call') {
                $wallet->update([
                    'value' => $wallet->value + $lock_hour->service->price
                ]);
            } elseif ($lock_hour->service->type == 'private lesson') {
                $wallet->update([
                    'value' => $wallet->value + (10 / 100) * $lock_hour->service->price
                ]);
            }
            $lock_hour->delete();
            return $this->returnData(200, 'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
}
