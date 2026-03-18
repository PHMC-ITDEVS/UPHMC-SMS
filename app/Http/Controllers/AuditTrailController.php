<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditTrailController extends Controller
{
    public function index()
    {
        return Inertia::render('audit-trail/index');
    }

    public function list(Request $request)
    {
        $search = $request->input('search', null);
        $limit = (int) $request->input('limit', 10);

        $data = AuditTrail::with('user')
            ->when($search, function ($query, $search) {
                $query->where('event', 'like', "%$search%")
                    ->orWhere('auditable_type', 'like', "%$search%")
                    ->orWhere('auditable_id', 'like', "%$search%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('username', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%");
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($limit > 0 ? $limit : 10);

        return response()->json(["status" => 1, "data" => $data], 200);
    }

    public function get($id)
    {
        $data = AuditTrail::with('user')->findOrFail($id);

        return response()->json(["status" => 1, "data" => $data], 200);
    }
}
