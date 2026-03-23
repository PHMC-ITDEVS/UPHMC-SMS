<?php

namespace App\Http\Controllers;

use App\Imports\ContactsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Http\Controllers\FileController;

use App\Models\Contact;

class PhonebookController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('phonebook/index');
    }

    public function list(Request $request)
    {
        $limit = (int) $request->input('limit', 10);
        $excludeGroupId = $request->input('exclude_group_id');

        $data = Contact::with('creator')
            ->when($request->search, function ($query, $search) 
            {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%$search%")
                        ->orWhere('phone_number', 'like', "%$search%");
                });
            })
            ->when($excludeGroupId, function ($query, $excludeGroupId) {
                $query->whereDoesntHave('groups', function ($groupQuery) use ($excludeGroupId) {
                    $groupQuery->where('contact_groups.id', $excludeGroupId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($limit > 0 ? $limit : 10);

        return response()->json(["status" => 1, "data" => $data], 200);
    }

    public function validateRequest(Request $request)
    {
        $rawPhone = (string) $request->input('phone_number');
        $normalizedPhone = '63' . $rawPhone;

        $validator = Validator::make([
            'name' => $request->input('name'),
            'phone_number' => $rawPhone,
            'phone_number_db' => $normalizedPhone,
            'notes' => $request->input('notes'),
        ], [
            'name' => 'required|string|max:50',
            'phone_number' => ['required', 'digits:10', 'regex:/^9\d{9}$/'],
            'phone_number_db' => ['required', Rule::unique('contacts', 'phone_number')->ignore($request->id)],
            'notes' => 'nullable|string|max:255',
        ], [
            'phone_number_db.unique' => 'The phone number has already been taken.',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->has('phone_number_db')) {
                $errors->add('phone_number', $errors->first('phone_number_db'));
            }

            return response()->json(["status" => 0, "errors" => $errors], 422);
        }

        return response()->json(['status' => 1, "message"=> "Success!"]);
    }

    public function create(Request $request)
    {
        $rawPhone = (string) $request->input('phone_number');
        $normalizedPhone = '63' . $rawPhone;

        $validator = Validator::make([
            'name' => $request->input('name'),
            'phone_number' => $rawPhone,
            'phone_number_db' => $normalizedPhone,
            'notes' => $request->input('notes'),
        ], [
            'name' => 'required|string|max:50',
            'phone_number' => ['required', 'digits:10', 'regex:/^9\d{9}$/'],
            'phone_number_db' => ['required', Rule::unique('contacts', 'phone_number')],
            'notes' => 'nullable|string|max:255',
        ], [
            'phone_number_db.unique' => 'The phone number has already been taken.',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            if ($errors->has('phone_number_db')) {
                $errors->add('phone_number', $errors->first('phone_number_db'));
            }
            return response()->json(["status" => 0, "errors" => $errors], 422);
        }

        $data = Contact::create([
            'name' => $request->input('name'),
            'phone_number' => $normalizedPhone,
            'notes' => $request->input('notes'),
            'created_by' => Auth::id(),
        ]);

        if($request->new_avatar)
        {
            FileController::uploadImage($data->id, "contact", $request->new_avatar);
        }

        return response()->json(["status" => 1, "message" => "Contact created successfully!"], 200);
    }

    public function get($id)
    {
        $data = Contact::with('creator')->findOrFail($id);
        return response()->json(["status" => 1, "data" => $data], 200);
    }

    public function update(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);

        $rawPhone = (string) $request->input('phone_number');
        $normalizedPhone = '63' . $rawPhone;

        $validator = Validator::make([
            'name' => $request->input('name'),
            'phone_number' => $rawPhone,
            'phone_number_db' => $normalizedPhone,
            'notes' => $request->input('notes'),
        ], [
            'name' => 'required|string|max:50',
            'phone_number' => ['required', 'digits:10', 'regex:/^9\d{9}$/'],
            'phone_number_db' => ['required', Rule::unique('contacts', 'phone_number')->ignore($contact->id)],
            'notes' => 'nullable|string|max:255',
        ], [
            'phone_number_db.unique' => 'The phone number has already been taken.',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            if ($errors->has('phone_number_db')) {
                $errors->add('phone_number', $errors->first('phone_number_db'));
            }
            return response()->json(["status" => 0, "errors" => $errors], 422);
        }

        $contact->update([
            'name' => $request->input('name'),
            'phone_number' => $normalizedPhone,
            'notes' => $request->input('notes'),
        ]);

        if($request->new_avatar)
        {
            FileController::uploadImage($contact->id, "contact", $request->new_avatar);
        }

        return response()->json(["status" => 1, "message" => "Contact updated successfully!"], 200);
    }

    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return response()->json(["status" => 1, "message" => "Contact deleted successfully!"], 200);
    }

    public function massRemove(Request $request)
    {
        $ids = $request->input('ids', []);
        Contact::whereIn('id', $ids)->delete();

        return response()->json(["status" => 1, "message" => "Selected contacts deleted successfully!"], 200);
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'errors' => $validator->errors(),
            ], 422);
        }

        $import = new ContactsImport(Auth::id());

        Excel::import($import, $request->file('file'));

        return response()->json([
            'status' => 1,
            'message' => 'Contacts imported successfully.',
            'summary' => [
                'imported' => $import->imported,
                'skipped' => $import->skipped,
                'errors' => $import->errors,
            ],
        ], 200);
    }

    public function template()
    {
        $content = implode("\n", [
            'name,phone_number,notes',
            'Juan Dela Cruz,09171234567,Sample contact',
            'Maria Santos,639181234567,VIP contact',
            'Pedro Reyes,9181234567,',
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="contacts_import_template.csv"',
        ]);
    }
}
