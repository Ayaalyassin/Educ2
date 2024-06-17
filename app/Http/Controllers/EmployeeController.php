<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Http\Requests\RegisterEmployeeRequest;
use App\Traits\GeneralTrait;

class EmployeeController extends Controller
{
    use GeneralTrait;
    private $uploadPath = "assets/images/employees";

    public function createEmployee(RegisterEmployeeRequest $request)
    {
        try {
            DB::beginTransaction();

            $data=User::create([
                'name'           => $request->name,//
                'email'          => $request->email,
                'password'       => $request->password,
                'address'         => $request->address,
                'governorate'    => $request->governorate,
                'birth_date'     =>$request->birth_date,
            ]);
            $role=Role::where('name','employee')->first();
            $data->assignRole($role);
            DB::commit();
            return $this->returnData($data,'operation completed successfully');
        }
        catch (\Exception $ex) {
            DB::rollBack();
            return $this->returnError("500",$ex->getMessage());

        }
    }

    public function updateEmployee($id,UpdateEmployeeRequest $request)
    {
        try {
            DB::beginTransaction();
            $data=User::where('id',$id)->first();
            if (!$data) {
                return $this->returnError("404",'Not found');
            }


            $data->update([
                'name'           => isset($request->name)? $request->name :$data->name,
                'email'          => isset($request->email)? $request->email :$data->email,
                'password'       => isset($request->password)? $request->password :$data->password,
                'address'         => isset($request->address)? $request->address :$data->address,
                'governorate'    => isset($request->governorate)? $request->governorate :$data->governorate,
                'birth_date'     => isset($request->birth_date)? $request->birth_date :$data->birth_date,
            ]);
            DB::commit();
            return $this->returnData($data,'operation completed successfully');
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->returnError("500",'Please try again later');

        }
    }

    public function getById($id)
    {
        try {
            $data=User::where('id',$id)->whereHas('roles',function ($query){
                $query->where('name',"employee");
            })->first();
            if (!$data) {
                return $this->returnError("404",'Not found');
            }
            $data->loadMissing(['roles']);
            return $this->returnData($data,'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(),'Please try again later');

        }
    }




    public function delete($id)
    {
        try {
            $data=User::where('id',$id)->first();
            if (!$data) {
                return $this->returnError("404",'Not found');
            }
            $data->delete();
            return $this->returnSuccessMessage('operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(),'Please try again later');

        }
    }

    public function getAll()
    {
        try {
            $data = User::whereHas('roles',function ($query){
                $query->where('name',"employee");
            })->get();
            return $this->returnData($data,'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(),'Please try again later');
        }
    }

}
