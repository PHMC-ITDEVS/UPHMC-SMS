<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();
        $role = strtolower((string) $user?->role_name);
        $variant = $role === 'admin' ? 'admin' : 'staff';

        return Inertia::render('dashboard', [
            'dashboard_variant' => $variant,
            'dashboard_stats' => $variant === 'admin'
                ? $this->buildAdminStats()
                : $this->buildStaffStats($user),
        ]);
    }

    protected function buildAdminStats(): array
    {
        $dates = collect(range(6, 0))
            ->map(fn (int $daysAgo) => Carbon::today()->subDays($daysAgo));

        $from = $dates->first()->copy()->startOfDay();

        $loginRows = AuditTrail::query()
            ->selectRaw('DATE(created_at) as login_date, COUNT(*) as total')
            ->where('event', 'logged_in')
            ->where('created_at', '>=', $from)
            ->groupBy('login_date')
            ->orderBy('login_date')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->login_date)->toDateString());

        $loginDaily = $dates->map(function (Carbon $date) use ($loginRows) {
            $key = $date->toDateString();
            $row = $loginRows->get($key);

            return [
                'date' => $key,
                'label' => $date->format('M d'),
                'count' => (int) ($row->total ?? 0),
            ];
        })->values();

        $todayLogins = (int) AuditTrail::query()
            ->where('event', 'logged_in')
            ->whereDate('created_at', Carbon::today())
            ->count();

        $activeAccounts = (int) AuditTrail::query()
            ->where('event', 'logged_in')
            ->where('created_at', '>=', $from)
            ->distinct('user_id')
            ->count('user_id');

        $smsDailyRows = DB::table('sms_messages')
            ->selectRaw('DATE(created_at) as usage_date, COUNT(*) as total_messages, COALESCE(SUM(total_recipients), 0) as total_recipients')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $from)
            ->groupBy('usage_date')
            ->orderBy('usage_date')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->usage_date)->toDateString());

        $smsDaily = $dates->map(function (Carbon $date) use ($smsDailyRows) {
            $key = $date->toDateString();
            $row = $smsDailyRows->get($key);

            return [
                'date' => $key,
                'label' => $date->format('M d'),
                'messages' => (int) ($row->total_messages ?? 0),
                'recipients' => (int) ($row->total_recipients ?? 0),
            ];
        })->values();

        $todaySms = DB::table('sms_messages')
            ->whereNull('deleted_at')
            ->whereDate('created_at', Carbon::today())
            ->selectRaw('COUNT(*) as total_messages, COALESCE(SUM(total_recipients), 0) as total_recipients')
            ->first();

        $smsDepartmentRows = DB::table('sms_messages')
            ->leftJoin('accounts', 'sms_messages.sender_id', '=', 'accounts.user_id')
            ->leftJoin('departments', 'accounts.department_id', '=', 'departments.id')
            ->selectRaw("COALESCE(departments.name, 'Unassigned') as department")
            ->selectRaw('COUNT(sms_messages.id) as total_messages')
            ->selectRaw('COALESCE(SUM(sms_messages.total_recipients), 0) as total_recipients')
            ->whereNull('sms_messages.deleted_at')
            ->groupByRaw("COALESCE(departments.name, 'Unassigned')")
            ->orderByDesc('total_recipients')
            ->get()
            ->map(fn ($row) => [
                'department' => $row->department,
                'total_messages' => (int) $row->total_messages,
                'total_recipients' => (int) $row->total_recipients,
            ])
            ->values();

        return [
            'login_summary' => [
                'today' => $todayLogins,
                'last_7_days' => (int) $loginDaily->sum('count'),
                'active_accounts' => $activeAccounts,
            ],
            'login_daily' => $loginDaily,
            'sms_summary' => [
                'today_messages' => (int) ($todaySms->total_messages ?? 0),
                'today_recipients' => (int) ($todaySms->total_recipients ?? 0),
                'last_7_days_messages' => (int) $smsDaily->sum('messages'),
                'last_7_days_recipients' => (int) $smsDaily->sum('recipients'),
            ],
            'sms_daily' => $smsDaily,
            'sms_by_department' => $smsDepartmentRows,
        ];
    }

    protected function buildStaffStats($user): array
    {
        $departmentId = $user?->account?->department_id;
        $departmentName = $user?->department_name;

        if (! $departmentId) {
            return [
                'has_department' => false,
                'message' => 'Your account is not assigned to a department yet.',
            ];
        }

        $dates = collect(range(6, 0))
            ->map(fn (int $daysAgo) => Carbon::today()->subDays($daysAgo));

        $from = $dates->first()->copy()->startOfDay();

        $departmentSmsDailyRows = DB::table('sms_messages')
            ->join('accounts', 'sms_messages.sender_id', '=', 'accounts.user_id')
            ->selectRaw('DATE(sms_messages.created_at) as usage_date')
            ->selectRaw('COUNT(sms_messages.id) as total_messages')
            ->selectRaw('COALESCE(SUM(sms_messages.total_recipients), 0) as total_recipients')
            ->whereNull('sms_messages.deleted_at')
            ->where('accounts.department_id', $departmentId)
            ->where('sms_messages.created_at', '>=', $from)
            ->groupBy('usage_date')
            ->orderBy('usage_date')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->usage_date)->toDateString());

        $departmentSmsDaily = $dates->map(function (Carbon $date) use ($departmentSmsDailyRows) {
            $key = $date->toDateString();
            $row = $departmentSmsDailyRows->get($key);

            return [
                'date' => $key,
                'label' => $date->format('M d'),
                'messages' => (int) ($row->total_messages ?? 0),
                'recipients' => (int) ($row->total_recipients ?? 0),
            ];
        })->values();

        $todaySms = DB::table('sms_messages')
            ->join('accounts', 'sms_messages.sender_id', '=', 'accounts.user_id')
            ->whereNull('sms_messages.deleted_at')
            ->where('accounts.department_id', $departmentId)
            ->whereDate('sms_messages.created_at', Carbon::today())
            ->selectRaw('COUNT(sms_messages.id) as total_messages, COALESCE(SUM(sms_messages.total_recipients), 0) as total_recipients')
            ->first();

        $staffUsage = DB::table('sms_messages')
            ->join('accounts', 'sms_messages.sender_id', '=', 'accounts.user_id')
            ->join('users', 'accounts.user_id', '=', 'users.id')
            ->join('role_user', function ($join) {
                $join->on('users.id', '=', 'role_user.user_id')
                    ->where('role_user.user_type', '=', 'App\Models\User');
            })
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->selectRaw("
                TRIM(
                    REGEXP_REPLACE(
                        CONCAT(
                            COALESCE(accounts.first_name, ''), ' ',
                            COALESCE(accounts.middle_name, ''), ' ',
                            COALESCE(accounts.last_name, '')
                        ),
                        '\\s+',
                        ' ',
                        'g'
                    )
                ) as full_name
            ")
            ->selectRaw('users.id as user_id')
            ->selectRaw('users.username')
            ->selectRaw('roles.name as role_name')
            ->selectRaw('COUNT(sms_messages.id) as total_messages')
            ->selectRaw('COALESCE(SUM(sms_messages.total_recipients), 0) as total_recipients')
            ->selectRaw('MAX(sms_messages.created_at) as last_used_at')
            ->whereNull('sms_messages.deleted_at')
            ->where('accounts.department_id', $departmentId)
            ->where('roles.name', 'staff')
            ->groupBy('users.id', 'users.username', 'roles.name', 'accounts.first_name', 'accounts.middle_name', 'accounts.last_name')
            ->orderByDesc('total_recipients')
            ->get()
            ->map(fn ($row) => [
                'user_id' => (int) $row->user_id,
                'name' => trim($row->full_name) !== '' ? trim($row->full_name) : $row->username,
                'username' => $row->username,
                'role_name' => strtoupper($row->role_name),
                'total_messages' => (int) $row->total_messages,
                'total_recipients' => (int) $row->total_recipients,
                'last_used_at' => $row->last_used_at
                    ? Carbon::parse($row->last_used_at)->timezone(config('app.timezone'))->format('Y-m-d H:i:s')
                    : null,
                'is_current_user' => (int) $row->user_id === (int) $user->id,
            ])
            ->values();

        $activeStaffSenders = $staffUsage
            ->where('total_messages', '>', 0)
            ->count();

        return [
            'has_department' => true,
            'department_name' => $departmentName,
            'viewer_role' => strtolower((string) $user?->role_name),
            'department_sms_summary' => [
                'today_messages' => (int) ($todaySms->total_messages ?? 0),
                'today_recipients' => (int) ($todaySms->total_recipients ?? 0),
                'last_7_days_messages' => (int) $departmentSmsDaily->sum('messages'),
                'last_7_days_recipients' => (int) $departmentSmsDaily->sum('recipients'),
                'active_staff_senders' => $activeStaffSenders,
            ],
            'department_sms_daily' => $departmentSmsDaily,
            'staff_sms_usage' => $staffUsage,
        ];
    }
}
