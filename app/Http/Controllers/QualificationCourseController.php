<?php

namespace App\Http\Controllers;

use App\Models\QualificationCourse;
use Illuminate\Http\Request;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\QualificationCourseRequest;
use App\Models\QualificationUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class QualificationCourseController extends Controller
{
    use GeneralTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $QualificationCourses = QualificationCourse::all();
            $data = [
                'qualificationCourses' => $QualificationCourses,
                'additionalVariable' => 2,
            ];
            return $this->returnData($data, 200);
        } catch (\Exception $ex) {
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
    public function store(QualificationCourseRequest $request)
    {
        try {
            DB::beginTransaction();

            $qualification_course = QualificationCourse::create([
                'name' => $request->name,
                'description' => $request->description,
                'date' => $request->date,
                'count_subscribers' => $request->count_subscribers,
                'price' => $request->price,
                'place' => $request->place,
                'remaining_number' => 0
            ]);

            DB::commit();
            return $this->returnData($qualification_course, 'operation completed successfully');
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
            $QualificationCourses = QualificationCourse::with('user')->find($id);
            if (!$QualificationCourses)
                return $this->returnError(404, 'Not found Qualification Course');
            DB::commit();
            return $this->returnData($QualificationCourses, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function show_my_courses()
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $courses = $user->qualification_users;

            DB::commit();
            return $this->returnData($courses, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function insert_into_courses($id)
    {
        try {
            DB::beginTransaction();
            $user = auth();
            $QualificationCourse = QualificationCourse::find($id);
            $userId = Auth::user();
            $now = Carbon::now();
            $qualificationUser = QualificationUser::where('qualification_id', '=', $id)
                ->where('user_id', '=', $userId->id)->first();

            $countUser = QualificationUser::where('qualification_id', '=', $id)->count();

            if (!$QualificationCourse)
                return $this->returnError(404, 'Not found Qualification Course');

            if ($QualificationCourse->date <= now()) {
                return $this->returnError(401, 'The course has begun');
            }
            if ($qualificationUser) {
                return $this->returnError(500, 'already insert');
            }
            if ($countUser >= $QualificationCourse->count_subscribers) {
                return $this->returnError(401, 'The number is complete');
            }
            if ($user->user()->wallet->value < $QualificationCourse->price)
                return $this->returnError(500, 'not Enough money in wallet');
            $user->user()->wallet->update([
                'value' => $user->user()->wallet->value - $QualificationCourse->price
            ]);
            $user->user()->wallet->save();
            $user->user()->qualification_users()->attach($id, ['created_at' => $now]);
            // $insert = QualificationUser::create([
            //     'user_id' => $user->id(),
            //     'qualification_id' => $id,
            // ]);


            DB::commit();
            return $this->returnData('successfully', 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(QualificationCourseRequest $request, $id)
    {

        try {
            DB::beginTransaction();
            $user = auth()->user();

            $qualification_course = $user->qualification_courses()->find($id);
            if (!$qualification_course)
                return $this->returnError("404", 'not found');

            $qualification_course->update([
                'name' => isset($request->name) ? $request->name : $qualification_course->name,
                'description' => isset($request->description) ? $request->description : $qualification_course->description,
                'date' => isset($request->date) ? $request->date : $qualification_course->date,
                'count_subscribers' => isset($request->count_subscribers) ?
                    $request->count_subscribers : $qualification_course->count_subscribers,
                'price' => isset($request->price) ? $request->price : $qualification_course->price,
            ]);


            DB::commit();
            return $this->returnData($qualification_course, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), 'Please try again later');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $user = auth()->user();
            $qualification_course = $user->qualification_courses()->find($id);
            if (!$qualification_course)
                return $this->returnError("404", 'not found');
            $qualification_course->delete();

            DB::commit();
            return $this->returnSuccessMessage('operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), 'Please try again later');
        }
    }
}
