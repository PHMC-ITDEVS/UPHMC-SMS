<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index()
    {
        return Inertia::render('department/index');
    }

    public function list(Request $request)
    {
        $search = $request->input('search', null);
        $limit = (int) $request->input('limit', 10);

        $data = Department::when($search, function ($query, $search) {
                $query->where('name', 'like', "%$search%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($limit > 0 ? $limit : 10);

        return response()->json(["status" => 1, "data" => $data], 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 400);
        }

        Department::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => Auth::id(),
        ]);

        return response()->json(['status' => 1, 'message' => 'Department created successfully!'], 200);
    }

    public function get($id)
    {
        $data = Department::findOrFail($id);

        return response()->json(["status" => 1, "data" => $data], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 400);
        }

        $department = Department::where('id', $id)->firstOrFail();

        $department->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json(['status' => 1, 'message' => 'Department updated successfully!'], 200);
    }

    public function destroy(Request $request, $id)
    {
        $department = Department::where('id', $id)->first();

        if (! $department) {
            return response()->json(["status" => 0, "message" => "Department record does not exist."], 400);
        }

        $department->delete();

        return response()->json(["status" => 1, "message" => "Department deleted successfully!"], 200);
    }
}
