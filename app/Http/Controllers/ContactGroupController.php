<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ContactGroup;
use Carbon\Carbon;

class ContactGroupController extends Controller
{
    public function index()
    {
        return Inertia::render('group/index');
    }

    public function list(Request $request)
    {
        $search = $request->input('search', null);
        $limit = (int) $request->input('limit', 10);
        $data = ContactGroup::when($search, function ($query, $search) 
            {
                $query->where('name', 'like', "$search%");
            })
            ->withCount('contacts')
            ->orderBy('created_at', 'desc')
            ->paginate($limit > 0 ? $limit : 10);

        return response()->json(["status" => 1, "data" => $data], 200);
    }

    public function create(Request $request)
    {
        $rules = [
            'description' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string'],
        ];

        $validatpor = Validator::make($request->all(), $rules);

        if ($validatpor->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validatpor->errors()], 400);
        }

        ContactGroup::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => Auth::id()
        ]);

        return response()->json(['status' => 1, 'message' => 'Group created successfully!'], 200);
    }

    public function get($id)
    {
        $data = ContactGroup::withCount('contacts')
            ->with([
                'contacts' => function ($query) {
                    $query->orderBy('name', 'asc');
                }
            ])
            ->findOrFail($id);
        
        return response()->json(["status" => 1, "data" => $data], 200);
    }

    public function attachContacts(Request $request, $id)
    {
        $group = ContactGroup::findOrFail($id);

        $contactIds = collect($request->input('contact_ids', []));

        if ($request->filled('contact_id')) {
            $contactIds->push($request->input('contact_id'));
        }

        $contactIds = $contactIds
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $validator = Validator::make([
            'contact_ids' => $contactIds->all(),
        ], [
            'contact_ids' => ['required', 'array', 'min:1'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $group->contacts()->syncWithoutDetaching(
            $contactIds->mapWithKeys(function ($contactId) {
                return [
                    $contactId => ['added_at' => Carbon::now()],
                ];
            })->all()
        );

        $group->loadCount('contacts');
        $group->load([
            'contacts' => function ($query) {
                $query->orderBy('name', 'asc');
            }
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Contact(s) added successfully!',
            'data' => $group,
        ], 200);
    }

    public function detachContact(Request $request, $id, $contactId)
    {
        $group = ContactGroup::findOrFail($id);

        $group->contacts()->detach($contactId);

        $group->loadCount('contacts');
        $group->load([
            'contacts' => function ($query) {
                $query->orderBy('name', 'asc');
            }
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Contact removed from group successfully!',
            'data' => $group,
        ], 200);
    }

    public function update(Request $request, $id)
    {
       $rules = [
            'description' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string'],
        ];

        $validatpor = Validator::make($request->all(), $rules);

        if ($validatpor->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validatpor->errors()], 400);
        }

        $role = ContactGroup::where('id', $id)->first();

        $role->update([
            'name' => $request->name,
            'description' => $request->description,
            'display_name' => $request->display_name,
        ]);

        return response()->json(['status' => 1, 'message' => 'Role created successfully!'], 200);
    }

    public function destroy(Request $request, $id)
    {
        $role = ContactGroup::where('id', $id)->first();

        if(!$role)
        {
            $error = ["status" => 0, "message" => "Role record does not exist."];
            return response()->json($error, 400);
        }

        $role->delete();

        return response()->json(["status" => 1, "message" => "Role deleted successfully!"], 200);
    }
}
