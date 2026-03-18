<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class PositionController extends Controller
{
    public function index()
    {
        return Inertia::render('position/index');
    }

    public function list(Request $request)
    {
        $search = $request->input('search', null);
        $limit = (int) $request->input('limit', 10);

        $data = Position::with('department')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%$search%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($limit > 0 ? $limit : 10);

        return response()->json(["status" => 1, "data" => $data], 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_id' => ['required','integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ],
        [
            'department_id.required' => 'The department field is required.'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 400);
        }

        Position::create([
            'department_id' => $request->department_id,
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => Auth::id(),
        ]);

        return response()->json(['status' => 1, 'message' => 'Position created successfully!'], 200);
    }

    public function get($id)
    {
        $data = Position::with('department')->findOrFail($id);

        return response()->json(["status" => 1, "data" => $data], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'department_id' => ['nullable', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 400);
        }

        $position = Position::where('id', $id)->firstOrFail();

        $position->update([
            'department_id' => $request->department_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json(['status' => 1, 'message' => 'Position updated successfully!'], 200);
    }

    public function destroy(Request $request, $id)
    {
        $position = Position::where('id', $id)->first();

        if (! $position) {
            return response()->json(["status" => 0, "message" => "Position record does not exist."], 400);
        }

        $position->delete();

        return response()->json(["status" => 1, "message" => "Position deleted successfully!"], 200);
    }
}
