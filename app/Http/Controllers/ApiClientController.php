<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ApiClientController extends Controller
{
    public function index()
    {
        return Inertia::render('api-client/index');
    }

    public function list(Request $request)
    {
        $search = $request->input('search', null);
        $limit = (int) $request->input('limit', 10);

        $data = ApiClient::with(['department'])
            ->when($search, function ($query, $search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('client_key', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($limit > 0 ? $limit : 10);

        return response()->json(['status' => 1, 'data' => $data], 200);
    }

    public function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 400);
        }

        return response()->json(['message' => 'Validation passed.']);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 400);
        }

        $credentials = ApiClient::makeCredentials();

        $client = ApiClient::create([
            'name' => $request->name,
            'department_id' => $request->department_id ?: null,
            'status' => $request->status,
            'allowed_ips' => $this->normalizeAllowedIps($request->allowed_ips),
            'meta' => ['description' => $request->description],
            'created_by' => Auth::id(),
            'client_key' => $credentials['client_key'],
            'client_secret_hash' => $credentials['client_secret_hash'],
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'API client created successfully.',
            'credentials' => [
                'client_key' => $client->client_key,
                'client_secret' => $credentials['raw_secret'],
                'bearer_token' => "{$client->client_key}.{$credentials['raw_secret']}",
            ],
        ], 200);
    }

    public function get($id)
    {
        $data = ApiClient::with(['department'])->findOrFail($id);

        return response()->json(['status' => 1, 'data' => $data], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 400);
        }

        $client = ApiClient::findOrFail($id);

        $client->update([
            'name' => $request->name,
            'department_id' => $request->department_id ?: null,
            'status' => $request->status,
            'allowed_ips' => $this->normalizeAllowedIps($request->allowed_ips),
            'meta' => ['description' => $request->description],
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'API client updated successfully.',
        ], 200);
    }

    public function regenerateSecret($id)
    {
        $client = ApiClient::findOrFail($id);
        $credentials = ApiClient::makeCredentials();

        $client->update([
            'client_secret_hash' => $credentials['client_secret_hash'],
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'API client secret regenerated successfully.',
            'credentials' => [
                'client_key' => $client->client_key,
                'client_secret' => $credentials['raw_secret'],
                'bearer_token' => "{$client->client_key}.{$credentials['raw_secret']}",
            ],
        ], 200);
    }

    public function destroy($id)
    {
        $client = ApiClient::find($id);

        if (! $client) {
            return response()->json([
                'status' => 0,
                'message' => 'API client record does not exist.',
            ], 400);
        }

        $client->delete();

        return response()->json([
            'status' => 1,
            'message' => 'API client deleted successfully.',
        ], 200);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'allowed_ips' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function normalizeAllowedIps(?string $allowedIps): ?array
    {
        $ips = collect(preg_split('/[\r\n,]+/', (string) $allowedIps))
            ->map(fn ($ip) => trim($ip))
            ->filter()
            ->values()
            ->all();

        return empty($ips) ? null : $ips;
    }
}
