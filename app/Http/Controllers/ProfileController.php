<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index()
    {
        return Inertia::render('account/profile');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        abort_unless($user, 403);

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'first_name' => ['required', 'string'],
            'middle_name' => ['nullable', 'string'],
            'last_name' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'errors' => $validator->errors()->toArray(),
            ], 400);
        }

        $user->update([
            'email' => $request->email,
        ]);

        $account = $user->account;

        if (!$account) {
            return response()->json([
                'success' => 0,
                'message' => 'Profile account not found.',
            ], 404);
        }

        $account->update([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
        ]);

        if ($request->new_avatar) {
            FileController::saveImage($account->account_number, 'accounts', $request->new_avatar);
        }

        return response()->json([
            'success' => 1,
            'message' => 'Profile updated successfully.',
        ]);
    }
}
