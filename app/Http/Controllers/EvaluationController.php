<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\ProfileTeacher;
use Illuminate\Http\Request;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\EvaluationRequest;
use App\Models\User;

class EvaluationController extends Controller
{
    use GeneralTrait;

    public function index()
    {
        //
    }


    public function store(EvaluationRequest $request)
    {

        try {
            DB::beginTransaction();

            $profile_student=auth()->user()->profile_student()->first();

            $teacher=ProfileTeacher::find($request->teacher_id);
            if(!$teacher)
                return $this->returnError("404", 'teacher not found');

            $evaluation= $profile_student->evaluation_as_student()->create([
                'rate' => $request->rate,
                'profile_teacher_id'=>$request->teacher_id
            ]);

            DB::commit();
            return $this->returnData($evaluation,'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }


    public function show(Evaluation $evaluation)
    {

    }


    public function update(Request $request, Evaluation $evaluation)
    {
        //
    }


    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $user=auth()->user()->profile_student()->first();
            $evaluation=$user->evaluation_as_student()->find($id);
            if(!$evaluation)
                return $this->returnError("404", 'not found');
            $evaluation->delete();

            DB::commit();
            return $this->returnSuccessMessage('operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }
}
