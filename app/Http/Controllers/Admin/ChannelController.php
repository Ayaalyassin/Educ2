<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChannelController extends Controller
{
    use GeneralTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(Request $request)
    {
        //
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
    public function connect(Request $request)
    {
        try {
            $user = auth()->user()->profile_teacher;
            if (!$user) {
                return $this->returnError(400, 'Token is Invalid');
            }
            $channels = Channel::get();
            foreach ($channels as $channel) {
                if ($channel->status == false) {
                    $channel->update([
                        'status' => true,
                        'teacherId' => $user->id,
                        'studentId' => $request->student_id
                    ]);
                    return $this->returnData(200, $channel->id);
                }
            }
            return $this->returnError(500, 'not found free channel');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function disconnect($id)
    {
        $channel = Channel::find($id);
        if (!$channel) {
            return $this->returnError(404, 'not Found channel');
        }
        $channel->update([
            'status' => false,
            'teacherId' => null,
            'studentId' => null
        ]);
    }
}
