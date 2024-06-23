<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\ProfileStudent;
use App\Models\ProfileTeacher;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminController extends Controller
{

    use GeneralTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            DB::beginTransaction();
            $teacher = ProfileTeacher::with('user')->where('status', 0)->get();
            DB::commit();
            return $this->returnData($teacher, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $teacher = ProfileTeacher::find($id);
            if (!$teacher) {
                return $this->returnError(404, 'not Found teacher');
            }
            if ($teacher->status == 1) {
                return $this->returnError(500, 'The teacher is accept');
            }
            $teacher->user()->delete();
            $teacher->delete();
            DB::commit();
            return $this->returnData($msg = "delete successfully", 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function accept_request_teacher($id)
    {
        try {
            DB::beginTransaction();
            $teacher = ProfileTeacher::find($id);
            if (!$teacher) {
                return $this->returnError(404, 'not Found teacher');
            }
            if ($teacher->status == 1) {
                return $this->returnError(500, 'The teacher is accept');
            }
            $teacher->update([
                'status' => 1
            ]);
            $teacher->save();
            DB::commit();
            return $this->returnData($msg = "accept request successfully", 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function get_all_teacher_unblock()
    {
        try {
            DB::beginTransaction();
            $teacher = ProfileTeacher::with('user')
                ->whereHas('user', function ($query) {
                    $query->whereDoesntHave('block');
                })
                ->with('domains')
                ->where('status', 1)
                ->get();

            DB::commit();
            return $this->returnData($teacher, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function get_all_teacher_block()
    {
        try {
            DB::beginTransaction();
            $teacher = User::whereHas('block')
                ->whereHas('profile_teacher')
                ->with('profile_teacher')
                ->get();
            DB::commit();
            return $this->returnData($teacher, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function get_all_teacher()
    {
        try {
            DB::beginTransaction();
            $teachers = ProfileTeacher::with('user')->with('domains')->where('status', 1)->get();
            DB::commit();
            return $this->returnData($teachers, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function count_all_teacher()
    {
        try {
            DB::beginTransaction();
            $users = User::whereHas('roles', function ($q) {
                $q->where('name', "teacher");
            })->whereHas('profile_teacher', function ($qu) {
                $qu->where('status', 1);
            })->count();
            DB::commit();
            return $this->returnData($users, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function count_unblock_teacher()
    {
        try {
            DB::beginTransaction();
            $users = User::whereHas('roles', function ($q) {
                $q->where('name', "teacher");
            })->whereHas('profile_teacher', function ($qu) {
                $qu->where('status', 1);
            })->whereDoesntHave('block')->count();
            DB::commit();
            return $this->returnData($users, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function count_block_teacher()
    {
        try {
            DB::beginTransaction();
            $users = User::whereHas('roles', function ($q) {
                $q->where('name', "teacher");
            })->whereHas('profile_teacher', function ($qu) {
                $qu->where('status', 1);
            })->whereHas('block')->count();
            DB::commit();
            return $this->returnData($users, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    //Student
    public function get_all_student_unblock()
    {
        try {
            DB::beginTransaction();
            $users = ProfileStudent::with('user')
                ->whereHas('user', function ($query) {
                    $query->whereDoesntHave('block');
                })->get();
            DB::commit();
            return $this->returnData($users, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function get_all_student_block()
    {
        try {
            DB::beginTransaction();
            $users = User::whereHas('block')
                ->whereHas('profile_student')
                ->with('profile_student')
                ->get();
            DB::commit();
            return $this->returnData($users, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function get_all_student()
    {
        try {
            DB::beginTransaction();
            $teachers = ProfileStudent::with('user')->get();
            DB::commit();
            return $this->returnData($teachers, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function count_student()
    {
        try {
            DB::beginTransaction();
            $users = User::whereHas('roles', function ($q) {
                $q->where('name', "student");
            })->whereHas('profile_student', function ($qu) {
            })->count();
            DB::commit();
            return $this->returnData($users, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function count_block_student()
    {
        try {
            DB::beginTransaction();
            $users = User::whereHas('roles', function ($q) {
                $q->where('name', "student");
            })->whereHas('profile_student', function ($qu) {
            })->whereHas('block')->count();
            DB::commit();
            return $this->returnData($users, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function count_unblock_student()
    {
        try {
            DB::beginTransaction();
            $users = User::whereHas('roles', function ($q) {
                $q->where('name', "student");
            })->whereHas('profile_student', function ($qu) {
            })->whereDoesntHave('block')->count();
            DB::commit();
            return $this->returnData($users, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function destroy_teacher($id)
    {
        try {
            DB::beginTransaction();
            //$user = User::where('role_id', 'teacher')->find($id);
            $user = User::where('id', $id)->whereHas('roles', function ($query) {
                $query->where('name', "teacher");
            })->first();
            if (!$user) {
                return $this->returnError(404, 'not Found teacher');
            }
            $user->profile_teacher()->delete();
            $user->block()->delete();
            $user->delete();
            DB::commit();
            return $this->returnData($msg = "delete successfully", 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function destroy_student($id)
    {
        try {
            DB::beginTransaction();
            //$user = User::where('role_id', 'student')->find($id);
            $user = User::where('id', $id)->whereHas('roles', function ($query) {
                $query->where('name', "student");
            })->first();
            if (!$user) {
                return $this->returnError(404, 'not Found student');
            }
            $user->profile_student()->delete();
            $user->block()->delete();
            $user->delete();
            DB::commit();
            return $this->returnData($msg = "delete successfully", 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function searchByName(Request $request)
    {
        $searchTerm = $request->input('search');
        if (!$searchTerm) {
            return response()->json(['message' => 'Please provide a search term'], 400);
        }
        $searchTerms = explode(' ', $searchTerm);
        $usersQuery = User::query();
        foreach ($searchTerms as $term) {
            $usersQuery->where('name', 'LIKE', '%' . $term . '%');
        }
        $users = $usersQuery->get();
        if ($users->isEmpty()) {
            return response()->json(['message' => 'No users found matching the search term'], 404);
        }
        return response()->json(['users' => $users], 200);
    }

    public function searchByAddress(Request $request)
    {
        $searchTerm = $request->input('search');
        if (!$searchTerm) {
            return response()->json(['message' => 'Please provide a search term'], 400);
        }
        $searchTerms = explode(' ', $searchTerm);
        $usersQuery = User::query();
        foreach ($searchTerms as $term) {
            $usersQuery->where('address', 'LIKE', '%' . $term . '%');
        }
        $users = $usersQuery->get();
        if ($users->isEmpty()) {
            return response()->json(['message' => 'No users found matching the search term'], 404);
        }
        return response()->json(['users' => $users], 200);
    }
}



    //public function insert_teacher()
    //{
    // for ($i = 26; $i < 50; $i++) {
    //     $user = User::create([
    //         'name'           => 'student'.$i,
    //         'email'          => 'student'.$i.'@gmail.com',
    //         'password'       => '12341234',
    //         'address'        => 'Syria',
    //         'governorate'    => 'Dam',
    //         'birth_date'     => Carbon::now(),
    //         'image'          => 'D://3.jpg',
    //         'role_id'        => 'student'
    //     ]);
    //     $credentials = ['email' => $user->email, 'password' => '12341234'];
    //     $token = JWTAuth::attempt($credentials);
    //     $user->token = $token;
    //     $role = Role::where('guard_name', '=', 'student')->first();
    //     $user->assignRole($role);
    //     $user->loadMissing(['roles']);
    //     if (!$token)
    //         return $this->returnError('Unauthorized', 400);
    //     $wallet = Wallet::create([
    //         'user_id' => $user->id,
    //         'number' => random_int(1000000000000, 9000000000000),
    //         'value' => 0,
    //     ]);
    // }

    // for ($i = 1; $i < 25; $i++) {
    //     $complete = ProfileTeacher::create([
    //         'user_id' => $i,
    //         'certificate' => 'dsds',
    //         'description' => 'dsdsd',
    //         'jurisdiction' => 'wewew',
    //         'domain' => 'ewwewq',
    //         'status' => 0,
    //         'assessing' => 0
    //     ]);
    // }

    // for ($i = 26; $i < 50; $i++) {
    //     $user = ProfileStudent::create([
    //         'user_id' =>$i,
    //         'educational_level' => 'eweq',
    //         'description' => 'yti',
    //         'assessing' => 0
    //     ]);
    // }
    // }
