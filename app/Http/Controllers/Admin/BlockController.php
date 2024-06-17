<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlockRequest;
use App\Models\Block;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlockController extends Controller
{
    use GeneralTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            DB::beginTransaction();
            $user = Block::with('user')->get();
            DB::commit();
            return $this->returnData($user, 200);
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
    public function store(BlockRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $user = User::find($id);
            if (!$user) {
                return $this->returnError(404, 'not found user');
            }
            $block = Block::create([
                'user_id' => $id,
                'reason' => isset($request->reason) ? $request->reason : null,
            ]);
            $block->save();
            DB::commit();
            return $this->returnData($block, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $block = Block::find($id);
            if (!$block) {
                return $this->returnError(404, 'not found user');
            }
            $block->delete();
            DB::commit();
            return $this->returnData('unblock successfully', 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
}
