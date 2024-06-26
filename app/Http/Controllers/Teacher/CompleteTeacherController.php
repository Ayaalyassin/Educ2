<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteRequest;
use App\Jobs\NotificationJobProfile;
use App\Models\CompleteTeacher;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompleteTeacherController extends Controller
{
    private $uploadPath = "assets/images/Complete_Teachers";
    use GeneralTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            DB::beginTransaction();
            $requestCompletes = CompleteTeacher::with(['teacher' => function ($q) {
                $q->select('id', 'user_id');
            }])
                ->with(['teacher.user' => function ($q) {
                    $q->select('id', 'name', 'address');
                }])
                ->where('status', '=', 0)
                ->get();

            DB::commit();
            return $this->returnData($requestCompletes, 'operation completed successfully');
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
    public function store(CompleteRequest $request)
    {
        try {
            DB::beginTransaction();

            $self_identity = null;
            if (isset($request->self_identity)) {
                $self_identity = $this->saveImage($request->self_identity, $this->uploadPath);
            }
            $cv = null;
            if (isset($request->cv)) {
                $cv = $this->saveAnyFile($request->cv, $this->uploadPath);
            }
            $user = auth()->user()->profile_teacher;
            $re = CompleteTeacher::where('teacher_id', $user->id)->first();
            if ($re) {
                return $this->returnError(400, 'already Request');
            }
            if (!$user) {
                return $this->returnError(400, 'Token is Invalid');
            }
            $requestComplete = $user->request_complete()->create([
                'cv' => $cv,
                'self_identity' => $self_identity,
                'phone' => isset($request->phone) ? $request->phone : null,
                'status' => 0
            ]);
            $requestComplete->save();
            DB::commit();
            return $this->returnData($requestComplete, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function update(CompleteRequest $request)
    {
        try {
            $self_identity = null;
            if (isset($request->self_identity)) {
                $self_identity = $this->saveImage($request->self_identity, $this->uploadPath);
            }
            $cv = null;
            if (isset($request->cv)) {
                $cv = $this->saveAnyFile($request->cv, $this->uploadPath);
            }
            DB::beginTransaction();
            $user = auth()->user()->profile_teacher;
            if (!$user) {
                return $this->returnError(400, 'Token is Invalid');
            }
            $requestComplete = $user->request_complete()->first();
            if (!$requestComplete) {
                return $this->returnError(404, 'request Complete Not found');
            }
            $user->request_complete()->update([
                'cv' => isset($request->cv) ? $cv : null,
                'self_identity' => isset($request->self_identity) ? $request->self_identity : null,
                'phone' => isset($request->phone) ? $request->phone : null,
                'status' => 0
            ]);
            // $request->save();
            DB::commit();
            return $this->returnData(200, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $requestComplete = CompleteTeacher::find($id);
            if (!$requestComplete) {
                return $this->returnError(404, 'not found request');
            }
            if ($requestComplete->status == 1) {
                return $this->returnError(500, 'The request is notarized');
            }
            $requestComplete->delete();
            NotificationJobProfile::dispatch($requestComplete->teacher,'was rejected','Your request to complete information has been rejected')->delay(Carbon::now()->addSeconds(2));
            DB::commit();
            return $this->returnData(200, 'delete order successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function accept_request_complete_teacher($id)
    {
        try {
            DB::beginTransaction();
            $rate = 0;
            $requestComplete = CompleteTeacher::with('teacher')->find($id);
            // return $requestComplete;
            if (!$requestComplete) {
                return $this->returnError(404, 'not found request');
            }
            if ($requestComplete->status == 1) {
                return $this->returnError(500, 'The request is notarized');
            }
            $requestComplete->update([
                'status' => 1
            ]);
            $requestComplete->save();
            if ($requestComplete->cv) {
                $rate = $rate + 1;
            }
            if ($requestComplete->self_identity) {
                $rate = $rate + 1;
            }
            if ($requestComplete->phone) {
                $rate = $rate + 1;
            }
            $requestComplete->teacher->update([
                'assessing' => $rate
            ]);
            $requestComplete->save();
            NotificationJobProfile::dispatch($requestComplete->teacher,'was accepted','Your request to complete information has been accepted')->delay(Carbon::now()->addSeconds(2));
            DB::commit();
            return $this->returnData(200, 'accept request complete successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getMessage(), $ex->getCode());
        }
    }
}
