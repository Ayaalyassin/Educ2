<?php

namespace App\Http\Controllers;

use App\Http\Requests\GovernorRequest;
use App\Models\Governor;
use Illuminate\Http\Request;

use App\Http\Requests\TransactionsRequest;
use App\Models\HistoryTransaction;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Cast\Object_;

use function PHPSTORM_META\type;

class GovernorController extends Controller
{
    use GeneralTrait;

    private $uploadPath = "assets/images/transaction";
    /**
     * Display a listing of the resource.
     */
    public function get_request_charge()
    {
        try {
            // DB::beginTransaction();
            // return Auth::user();
            $convenors = DB::table('governors')
                ->join('wallets', 'governors.wallet_id', '=', 'wallets.id')
                ->join('users', 'users.id', '=', 'wallets.user_id')
                ->where('governors.type', 'charge')
                ->select(
                    'governors.id',
                    'governors.amount',
                    'governors.image_transactions',
                    'wallets.value as walletsValue',
                    'users.name',
                    'users.email',
                    'users.address',
                    'users.governorate',
                    // 'users.roles as userRole '
                )
                ->get();
            DB::commit();
            return $this->returnData($convenors, 'Request recharge');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function get_request_recharge()
    {
        try {
            DB::beginTransaction();
            $convenor = DB::table('governors')
                ->join('wallets', 'governors.wallet_id', '=', 'wallets.id')
                ->join('users', 'users.id', '=', 'wallets.user_id')
                ->where('governors.type', 'recharge')
                ->select(
                    'governors.id',
                    'governors.amount',
                    'wallets.value as walletsValue',
                    'users.name',
                    'users.email',
                    'users.address',
                    'users.governorate',
                    // 'users.role_id as userRole '
                )
                ->get();
            DB::commit();
            return $this->returnData($convenor, 'Request recharge');
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
    public function store(GovernorRequest $request)
    {
        try {
            DB::beginTransaction();

            $image_transactions = null;
            if (isset($request->image_transactions)) {
                $image_transactions = $this->saveImage($request->image_transactions, $this->uploadPath);
            }
            $user = auth()->user()->wallet;
            if ($request->type == 'recharge') {
                if ($request->amount > $user->value) {
                    return $this->returnError(400, 'not Enough money in wallet');
                }
                $user->update([
                    'value' => $user->value - $request->amount,
                ]);
                $user->save();
            }
            if ($request->type == 'charge' and $request->image_transactions == null) {
                return $this->returnError(500, 'You must input image transaction');
            }
            $convenor = $user->governor()->create([
                'amount' => isset($request->amount) ? $request->amount : null,
                'type' => isset($request->type) ? $request->type : null,
                'image_transactions' => $image_transactions,

            ]);
            $convenor->save();
            DB::commit();
            return $this->returnData($convenor, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        try {
            DB::beginTransaction();
            $userWallet = auth()->user()->wallet->id;
            $data = Governor::with('wallet')->where('wallet_id', $userWallet)->where('type', 'charge')
                ->get();
            DB::commit();
            return $this->returnData($data, 'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function showMyRequestRecharge()
    {
        try {
            DB::beginTransaction();
            $userWallet = auth()->user()->wallet->id;
            $data = Governor::with('wallet')->where('wallet_id', $userWallet)->where('type', 'recharge')
                ->get();
            DB::commit();
            return $this->returnData($data, 'operation completed successfully');
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

    public function destroy(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $history = Governor::with('wallet')->with('wallet.user')->find($id);
            $convenor = Governor::find($id);
            if (!$convenor) {
                return $this->returnError(404, 'not found request');
            }
            $convenor->delete();
            $historyTrn = HistoryTransaction::create([
                'name' => $history->wallet->user->name,
                'image' => $history->image_transactions,
                'type' => $history->type,
                'value' => $history->amount,
                'status' => 'Unaccepted',
                'case' => $request->case
            ]);
            DB::commit();
            return $this->returnData(200, 'delete order successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function accept_request_charge($id)
    {
        try {
            DB::beginTransaction();
            $history = Governor::with('wallet')->with('wallet.user')->where('type', 'charge')->find($id);
            $convenor = Governor::with('wallet')->where('type', 'charge')->find($id);
            if (!$convenor) {
                return $this->returnError(404, 'not found request');
            }
            $convenor->wallet->update([
                'value' => $convenor->amount + $convenor->wallet->value,
            ]);
            $convenor->wallet->save();
            $convenor->delete();
            $historyTrn = HistoryTransaction::create([
                'name' => $history->wallet->user->name,
                'image' => $history->image_transactions,
                'type' => 'charge',
                'value' => $history->amount,
                'status' => 'accepted'
            ]);
            DB::commit();
            return $this->returnData(200, 'charge successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function accept_request_recharge($id)
    {
        try {
            DB::beginTransaction();
            $history = Governor::with('wallet')->with('wallet.user')->where('type', 'recharge')->find($id);
            $convenor = Governor::with('wallet')->where('type', 'recharge')->find($id);
            if (!$convenor) {
                return $this->returnError(404, 'not found request');
            }
            $convenor->delete();
            $historyTrn = HistoryTransaction::create([
                'name' => $history->wallet->user->name,
                // 'image' => $history->image_transactions,
                'type' => 'recharge',
                'value' => $history->amount,
                'status' => 'accepted'
            ]);
            DB::commit();
            return $this->returnData(200, 'recharge successfully');
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getMessage(), $ex->getCode());
        }
    }
}
