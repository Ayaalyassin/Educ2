<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\ProfileStudent;
use Illuminate\Http\Request;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\NoteRequest;
use App\Models\User;

class NoteController extends Controller
{
    use GeneralTrait;


    public function index()
    {
        try {
            $profile_teacher=auth()->user()->profile_teacher()->first();
            $services_ids=$profile_teacher->service_teachers()->pluck('id');
            $profile_students=[];
            if($services_ids && $profile_teacher)
                $profile_students=ProfileStudent::whereHas('hour_lock',function ($query)use ($services_ids){
                    $query->where('status',1)->whereIn('service_id',$services_ids);
                })->with('note_as_student')->get();

            return $this->returnData($profile_students,'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500",$ex->getMessage());
        }
    }


    public function store(NoteRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user()->profile_teacher()->first();

            $student = ProfileStudent::find($request->student_id);
            if (!$student)
                return $this->returnError(404, 'Profile Student Id Not Found');

            $note = $user->note_as_teacher()->create([
                'note' => $request->note,
                'profile_student_id' => $request->student_id
            ]);

            DB::commit();
            return $this->returnData($note, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", $ex->getMessage());
        }
    }


    public function show(Note $note)
    {
        //
    }

    public function update(Request $request, Note $note)
    {
        //
    }


    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $user = auth()->user()->profile_teacher()->first();
            $note = $user->note_as_teacher()->find($id);
            if (!$note)
                return $this->returnError("404", 'note not found');
            $note->delete();

            DB::commit();
            return $this->returnSuccessMessage('operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError("500", 'Please try again later');
        }
    }
}
