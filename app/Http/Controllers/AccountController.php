<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Account;
use App\Models\User;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Library\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Carbon\Carbon;
class AccountController extends Controller
{
    //
    public function index(Request $request)
    {
        return Inertia::render('account/index');
    }

    public function list(Request $request)
    {
        $search = $request->search;
        $column = $request->column;
        $order = $request->order;
        $start_date = isset($request->start_date) ? Carbon::parse($request->start_date)->copy()->startOfDay()->toDateTimeString() : null;
        $end_date = isset($request->end_date) ? Carbon::parse($request->end_date)->copy()->endOfDay()->toDateTimeString() : null;


        $data = Account::with(['user', 'department', 'position'])
            ->when($search, function ($query, $value) 
            {
                $query->whereRaw("concat(first_name,' ',last_name) LIKE ?",['%'.$value.'%'])
                    ->orWhere("last_name",'LIKE','%'.$value.'%');
            })
            ->when($start_date && $end_date, function ($query) use ($start_date, $end_date)
                {
                    $query->whereBetween('created_at',[$start_date, $end_date]);
                })
            ->when($order && $column, function ($query) use ($column, $order) 
                {
                    $query->orderBy($column, $order);
                }, 
                    function ($query) 
                    {
                        $query->orderBy("id", "desc");
                    }
                )
            ->paginate($request->limit ?? 10);

        return response()->json(["status" => 1,"data" => $data], 200);
    }

    public function get($id)
    {
        $data = Account::with(["user", "department", "position"])
            ->where("account_number",$id)
            ->first();

        return response()->json(["status"=>"success", "data"=>$data], 200);
    }

    public function validateRequest(Request $request)
    {
        $rules = [];
        $password = ["nullable|"];
        $userId = $this->resolveUserId($request);
    
        if (!$userId)
        {
            $password = [ "required", "alpha_dash", "same:confirm_password"];
        }

        switch ($request->step_no) 
        {
            case 0:
                $rules = [
                    'role_name' => ['required', 'string'],
                    'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
                    'username' => ['required', 'string', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)],
                    'password' => $password,
                ];
                break;
        
            case 1:
                $rules = [
                    'first_name' => ['required', 'string'],
                    'last_name' => ['required', 'string'],
                    'middle_name' => ['nullable', 'string'],
                    'department_id' => ['nullable', 'exists:departments,id'],
                    'position_id' => ['nullable', 'exists:positions,id'],
                ];
                break;
        
            default:
                $rules = [];
                break;
        }

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) 
        {
            $log = ['success' => 0, 'errors' => $validator->getMessageBag()->toArray()];
            return response()->json($log, 400);
        }

        return response()->json(['success' => 1,"message"=>"Success!"]);
    }
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_name' => ['required', 'string'],
            'email' => ['required', 'email', "unique:users,email"],
            'username' => ['required', 'string', 'alpha_dash', "unique:users,username"],
            'password' => [ "required", "alpha_dash", "same:confirm_password"],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'middle_name' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
        ]);

        if($validator->fails()) 
        {
            $log = ['success' => 0, 'errors' => $validator->getMessageBag()->toArray()];
            return response()->json($log, 400);
        }

        try 
        {
            return DB::transaction(function () use ($request)
            {
                $user = User::create([
                    "username"=>$request->username,
                    "email"=>$request->email,
                    "password"=>bcrypt($request->password),
                    "must_change_password" => true,
                ]);
        
                $account_number = $ref = Helper::ref_number("A",20);
                $account = Account::create([
                    "user_id"=>$user->id,
                    "department_id"=>$request->department_id,
                    "position_id"=>$request->position_id,
                    "account_number"=>$account_number,
                    "first_name"=>$request->first_name,
                    "middle_name"=>$request->middle_name,
                    "last_name"=>$request->last_name
                ]);
                
                $user->addRole(strtolower($request->role_name));
        
                if ($request->new_avatar) FileController::saveImage($account->account_number,"accounts",$request->new_avatar);
        
                return response()->json(['success' => 1,"message"=>"Success!"]);
            });
        } 
        catch(\Exception $e) 
        {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 400);
        }
    }
    public function update($id,Request $request)
    {
        $account = Account::where("id",$id)->firstOrFail();
        $userId = $request->input('user_id', $account->user_id);

        $validator = Validator::make($request->all(), [
            'id' => ['required', "exists:accounts,id"],
            'role_name' => ['required', 'string'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'username' => ['required', 'string', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'middle_name' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
        ]);

        if($validator->fails()) 
        {
            $log = ['success' => 0, 'errors' => $validator->getMessageBag()->toArray()];
            return response()->json($log, 400);
        }

        $account->update([
            "department_id" => $request->department_id,
            "position_id" => $request->position_id,
            "first_name" => $request->first_name,
            "middle_name" => $request->middle_name,
            "last_name" => $request->last_name
        ]);

        $user = User::where("id",$account->user_id)
            ->first();

        if($user)
        {
            $user->update([
                "email"=>$request->email,
                "username"=>$request->username,
            ]);

            if ($user->role_name && $user->role_name !== $request->role_name) {
                $user->removeRole($user->role_name);
            }

            if ($request->role_name && $user->role_name !== $request->role_name) {
                $user->addRole($request->role_name);
            }
        }
        
        if ($request->new_avatar) FileController::saveImage($account->account_number,"accounts",$request->new_avatar);

        return response()->json(['success' => 1,"message"=>"Success!"]);
    }

    public function regeneratePassword($id)
    {
        $account = Account::with('user')->findOrFail($id);
        $user = $account->user;

        if (! $user) {
            return response()->json([
                'success' => 0,
                'message' => 'User account not found.',
            ], 404);
        }

        $temporaryPassword = $this->makeTemporaryPassword();

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
            'password_changed_at' => null,
            'remember_token' => Str::random(60),
        ])->save();

        return response()->json([
            'success' => 1,
            'message' => 'Temporary password generated successfully.',
            'credentials' => [
                'username' => $user->username,
                'temporary_password' => $temporaryPassword,
            ],
        ]);
    }

    private function resolveUserId(Request $request): ?int
    {
        $userId = $request->input('user_id');

        if ($userId !== null && $userId !== '') {
            return (int) $userId;
        }

        $accountId = $request->input('id');

        if ($accountId === null || $accountId === '') {
            return null;
        }

        return Account::where('id', $accountId)->value('user_id');
    }

    private function makeTemporaryPassword(): string
    {
        return strtoupper(Str::random(4)) . rand(1000, 9999);
    }

    public function destroy($account_number, Request $request)
    {
        $account = Account::where("account_number", $account_number)->firstOrFail();
        $account->delete();

        return response()->json(['success' => 1,"message"=>"Success!"]);
    }
}
