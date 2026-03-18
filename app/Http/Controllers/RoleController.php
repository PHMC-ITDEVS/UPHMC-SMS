<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return Inertia::render('role/index');
    }

    public function list(Request $request)
    {
        $search = $request->input('search', null);
        $data = Role::when($search, function ($query, $search) 
            {
                $query->where('name', 'like', "%$search%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json(["status" => 1, "data" => $data], 200);
    }

    public function create(Request $request)
    {
        $rules = [
            'description' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string'],
            'display_name' => ['required', 'string'],
        ];

        $validatpor = Validator::make($request->all(), $rules);

        if ($validatpor->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validatpor->errors()], 400);
        }

        Role::create([
            'name' => $request->name,
            'description' => $request->description,
            'display_name' => $request->display_name,
        ]);

        return response()->json(['status' => 1, 'message' => 'Role created successfully!'], 200);
    }

    public function get($id)
    {
        $data = Role::findOrFail($id);
        
        return response()->json(["status" => 1, "data" => $data], 200);
    }


    public function update(Request $request, $id)
    {
        $rules = [
            'description' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string'],
            'display_name' => ['required', 'string'],
        ];

        $validatpor = Validator::make($request->all(), $rules);

        if ($validatpor->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validatpor->errors()], 400);
        }

        $role = Role::where('id', $id)->first();

        $role->update([
            'name' => $request->name,
            'description' => $request->description,
            'display_name' => $request->display_name,
        ]);

        return response()->json(['status' => 1, 'message' => 'Role created successfully!'], 200);
    }

    public function destroy(Request $request, $id)
    {
        $role = Role::where('id', $id)->first();

        if(!$role)
        {
            $error = ["status" => 0, "message" => "Role record does not exist."];
            return response()->json($error, 400);
        }

        $role->delete();

        return response()->json(["status" => 1, "message" => "Role deleted successfully!"], 200);
    }
}
