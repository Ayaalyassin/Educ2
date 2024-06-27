<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\NotificationJobProfile;
use App\Models\Block;
use App\Models\ProfileStudent;
use App\Models\ProfileTeacher;
use App\Models\RejectRequest;
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

    public function destroy(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $cases = $request->input('case');
            $teacher = ProfileTeacher::find($id);
            if (!$teacher) {
                return $this->returnError(404, 'not Found teacher');
            }
            if ($teacher->status == 1) {
                return $this->returnError(500, 'The teacher is accept');
            }

            $reject_requests = RejectRequest::create([
                'name' => $teacher->user->name,
                'case' => $cases,
                'type' => 'join Request'
            ]);
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

            NotificationJobProfile::dispatch($teacher, 'تم الموافقة', 'تم الموافقة على طلبك')->delay(Carbon::now()->addSeconds(2));

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
                ->with('user.wallet')
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
                ->with('wallet')
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
            $teachers = ProfileTeacher::with('user')->with('domains')
                ->with('user.wallet')
                ->where('status', 1)->get();
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
                })
                ->with('user.wallet')
                ->get();
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
                ->with('wallet')
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
            $teachers = ProfileStudent::with('user')->with('user.wallet')->get();
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

            $teacher = ProfileTeacher::find($id);
            if (!$teacher) {
                return $this->returnError(404, 'Student not found');
            }
            $teacher->domains()->each(function ($domain) {
                $domain->delete();
            });
            $user = $teacher->user;
            $teacher->day()->each(function ($day) {
                $day->hours()->each(function ($hour) {
                    $hour->delete();
                });
                $day->delete();
            });
            if ($teacher->request_complete) {
                $teacher->request_complete->delete();
            }
            if ($user) {
                $block = $user->block;
                if ($block) {
                    $block->delete();
                }
                $user->delete();
            }

            $teacher->delete();
            DB::commit();
            return $this->returnData(200, __("backend.Delete successfully", [], app()->getLocale()));
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function destroy_student($id)
    {
        try {
            DB::beginTransaction();

            $student = ProfileStudent::find($id);
            if (!$student) {
                return $this->returnError(404, 'Student not found');
            }
            $user = $student->user;
            if ($user) {
                $block = $user->block;
                if ($block) {
                    $block->delete();
                }
                $user->delete();
            }
            $student->delete();
            DB::commit();
            return $this->returnData(200, __("backend.Delete successfully", [], app()->getLocale()));
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

    public function get_all_reject_join_request()
    {
        try {
            DB::beginTransaction();
            $teacher = RejectRequest::where('type', 'join Request')->get();

            DB::commit();
            return $this->returnData($teacher, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
    public function get_all_reject_complete_request()
    {
        try {
            DB::beginTransaction();
            $teacher = RejectRequest::where('type', 'complete Request')->get();

            DB::commit();
            return $this->returnData($teacher, 200);
        } catch (\Exception $ex) {
            DB::rollback();
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
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
