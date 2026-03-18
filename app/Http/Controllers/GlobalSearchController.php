<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AuditTrail;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Department;
use App\Models\Position;
use App\Models\Role;
use App\Models\SmsMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class GlobalSearchController extends Controller
{
    private const DEFAULT_LIMIT = 5;
    private const MAX_LIMIT = 10;
    private const ADMIN_ROLE = 'admin';

    public function autocomplete(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));
        $limit = max(1, min((int) $request->input('limit', self::DEFAULT_LIMIT), self::MAX_LIMIT));

        if ($query === '') {
            return response()->json($this->emptyResponse());
        }

        $isAdmin = $this->isAdmin();

        return response()->json([
            ...($isAdmin ? [
                'accounts'     => $this->searchAccounts($query, $limit),
                'roles'        => $this->searchRoles($query, $limit),
                'departments'  => $this->searchDepartments($query, $limit),
                'positions'    => $this->searchPositions($query, $limit),
                'audit_trails' => $this->searchAuditTrails($query, $limit),
            ] : []),
            'contacts' => $this->searchContacts($query, $limit),
            'groups'   => $this->searchGroups($query, $limit),
            'sms'      => $this->searchSms($query, $limit),
        ]);
    }

    protected function searchAccounts(string $query, int $limit): array
    {
        return Account::with(['user:id,username,email', 'department:id,name', 'position:id,name'])
            ->where(function (Builder $builder) use ($query) {
                $builder->where('first_name', 'ilike', $this->like($query))
                    ->orWhere('middle_name', 'ilike', $this->like($query))
                    ->orWhere('last_name', 'ilike', $this->like($query))
                    ->orWhere('account_number', 'ilike', $this->like($query))
                    ->orWhereHas('user', fn (Builder $q) =>
                        $q->where('username', 'ilike', $this->like($query))
                          ->orWhere('email', 'ilike', $this->like($query))
                    );
            })
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(function (Account $account) {
                $name = $this->buildFullName($account->first_name, $account->middle_name, $account->last_name);

                return $this->result(
                    $account->id,
                    $name ?: ($account->user?->username ?? $account->account_number),
                    $this->joinFields($account->user?->username, $account->department?->name, $account->position?->name),
                    route('account.index'),
                );
            })
            ->all();
    }

    protected function searchRoles(string $query, int $limit): array
    {
        return Role::where(function (Builder $builder) use ($query) {
                $builder->where('name', 'ilike', $this->like($query))
                    ->orWhere('display_name', 'ilike', $this->like($query))
                    ->orWhere('description', 'ilike', $this->like($query));
            })
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Role $role) => $this->result(
                $role->id,
                $role->display_name ?: $role->name,
                $role->description,
                route('role.index'),
            ))
            ->all();
    }

    protected function searchDepartments(string $query, int $limit): array
    {
        return Department::where(function (Builder $builder) use ($query) {
                $builder->where('name', 'ilike', $this->like($query))
                    ->orWhere('description', 'ilike', $this->like($query));
            })
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Department $department) => $this->result(
                $department->id,
                $department->name,
                $department->description,
                route('department.index'),
            ))
            ->all();
    }

    protected function searchPositions(string $query, int $limit): array
    {
        return Position::with('department:id,name')
            ->where(function (Builder $builder) use ($query) {
                $builder->where('name', 'ilike', $this->like($query))
                    ->orWhere('description', 'ilike', $this->like($query))
                    ->orWhereHas('department', fn (Builder $q) =>
                        $q->where('name', 'ilike', $this->like($query))
                    );
            })
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Position $position) => $this->result(
                $position->id,
                $position->name,
                $position->department?->name,
                route('position.index'),
            ))
            ->all();
    }

    protected function searchContacts(string $query, int $limit): array
    {
        return Contact::where(function (Builder $builder) use ($query) {
                $builder->where('name', 'ilike', $this->like($query))
                    ->orWhere('phone_number', 'ilike', $this->like($query));
            })
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Contact $contact) => $this->result(
                $contact->id,
                $contact->name,
                $contact->phone_number,
                route('phonebook.index'),
            ))
            ->all();
    }

    protected function searchGroups(string $query, int $limit): array
    {
        return ContactGroup::withCount('contacts')
            ->where(function (Builder $builder) use ($query) {
                $builder->where('name', 'ilike', $this->like($query))
                    ->orWhere('description', 'ilike', $this->like($query));
            })
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (ContactGroup $group) => $this->result(
                $group->id,
                $group->name,
                "{$group->contacts_count} contact(s)",
                route('contact_group.index'),
            ))
            ->all();
    }

    protected function searchSms(string $query, int $limit): array
    {
        return SmsMessage::with('sender')
            ->where('message_body', 'ilike', $this->like($query))
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (SmsMessage $message) => $this->result(
                $message->id,
                str($message->message_body)->limit(70)->toString(),
                $this->joinFields(
                    ucfirst((string) $message->type),
                    $message->status?->value ?? (string) $message->status,
                    $message->sender?->name,
                ),
                route('sms.index'),
            ))
            ->all();
    }

    protected function searchAuditTrails(string $query, int $limit): array
    {
        return AuditTrail::with('user:id,username,email')
            ->where(function (Builder $builder) use ($query) {
                $builder->where('event', 'ilike', $this->like($query))
                    ->orWhere('auditable_type', 'ilike', $this->like($query))
                    ->orWhereHas('user', fn (Builder $q) =>
                        $q->where('username', 'ilike', $this->like($query))
                          ->orWhere('email', 'ilike', $this->like($query))
                    );
            })
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (AuditTrail $trail) => $this->result(
                $trail->id,
                ucfirst($trail->event),
                $this->joinFields(
                    class_basename($trail->auditable_type),
                    '#' . $trail->auditable_id,
                    $trail->user?->username,
                ),
                route('audit-trail.index'),
            ))
            ->all();
    }

    private function isAdmin(): bool
    {
        return strtolower((string) auth()->user()?->role_name) === self::ADMIN_ROLE;
    }

    private function like(string $query): string
    {
        return "%{$query}%";
    }

    private function result(int $id, string $title, ?string $subtitle, string $url): array
    {
        return compact('id', 'title', 'subtitle', 'url');
    }

    private function joinFields(?string ...$fields): string
    {
        return collect($fields)->filter()->implode(' • ');
    }

    private function buildFullName(?string ...$parts): string
    {
        return collect($parts)->filter()->implode(' ');
    }

    protected function emptyResponse(): array
    {
        return array_fill_keys(
            ['accounts', 'roles', 'departments', 'positions', 'contacts', 'groups', 'sms', 'audit_trails'],
            []
        );
    }
}
