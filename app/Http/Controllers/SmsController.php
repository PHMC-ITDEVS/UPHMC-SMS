<?php

namespace App\Http\Controllers;

use App\Enums\SmsStatus;
use App\Http\Requests\SendSmsRequest;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\SmsMessage;
use App\Services\Sms\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Library\Helper;
use Illuminate\Database\Eloquent\Builder;

class SmsController extends Controller
{

    public function __construct(private readonly SmsService $smsService) {}

    public function index()
    {
        return Inertia::render('sms/index');
    }  
    
    public function list(Request $request): JsonResponse
    {
        $search = $request->input('search', null);

        $data = $this->visibleSmsMessagesQuery()
            ->when($search, function ($query, $search) 
            {
                $query->where('message_body', 'like', "%$search%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json(["status" => 1, "data" => $data], 200);
    }

    public function get($id): JsonResponse
    {
        $data = $this->visibleSmsMessagesQuery()
            ->findOrFail($id);

        return response()->json(["status" => 1, "data" => $data], 200);
    }

    protected function visibleSmsMessagesQuery(): Builder
    {
        $user = Auth::user();
        $isAdmin = strtoupper((string) $user?->role_name) === 'ADMIN';
        $departmentId = $user?->account?->department_id;

        return SmsMessage::with(['sender', 'recipients', 'recipients.contact'])
            ->when(! $isAdmin, function (Builder $query) use ($departmentId) {
                $query->whereHas('sender.account', function (Builder $accountQuery) use ($departmentId) {
                    if ($departmentId) {
                        $accountQuery->where('department_id', $departmentId);
                        return;
                    }

                    $accountQuery->whereNull('department_id');
                });
            });
    }

    public function validateRequest(Request $request)
    {
        $request->validate([
            'body' => ['required', 'string', 'max:160'],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*.type' => ['required', 'string', 'in:number,contact,group'],
            'recipients.*.value' => ['required'],
            'send_type' => ['required', 'string', 'in:immediate,scheduled'],
            'scheduled_at' => ['exclude_unless:send_type,scheduled', 'required', 'date'],
        ]);

        return response()->json(['message' => 'Validation passed.']);
    }  
    
    public function create(SendSmsRequest $request)
    {
        $rules = [
            'body' => ['required', 'string', 'max:160'],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*.type' => ['required', 'string', 'in:number,contact,group'],
            'recipients.*.value' => ['required'],
            'send_type' => ['required', 'string', 'in:immediate,scheduled'],
            'scheduled_at' => ['exclude_unless:send_type,scheduled', 'required', 'date'],
        ];

        $validatpor = Validator::make($request->all(), $rules);

        if ($validatpor->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validatpor->errors()], 400);
        }

        $resolved = $this->resolveRecipients($request->input('recipients'));
        Log::info('Resolved recipients', ['input' => $request->input('recipients'), 'resolved' => $resolved]);
        if (empty($resolved['recipients'])) {
            return response()->json([
                'message' => 'No valid recipients found.',
            ], 422);
        }

        $sms_service_to_use = $request->send_type === 'scheduled' ? 'scheduleRecipients' : 'sendToRecipients';

        $args = [
            'body'       => $request->input('body'),
            'recipients' => $resolved['recipients'],
            'senderId'   => Auth::id(),
        ];

        if ($sms_service_to_use === 'scheduleRecipients') {
            $args['scheduledAt'] = \Carbon\Carbon::parse($request->input('scheduled_at'));
        }

        $message = $this->smsService->$sms_service_to_use(...$args);

        return response()->json(['status' => 1, 'message' => 'SMS queued successfully.']);
    }

    // update function for the scheduled sms since it is not queue or complete, or it have status draft only
    public function update($id, SendSmsRequest $request): JsonResponse
    {
        $message = SmsMessage::with(['recipients'])
            ->findOrFail($id);

        if ($message->type !== 'scheduled' || $message->status !== SmsStatus::DRAFT) {
            return response()->json([
                'message' => 'Only scheduled draft SMS records can be updated.',
            ], 422);
        }

        if ($request->input('send_type') !== 'scheduled') {
            return response()->json([
                'message' => 'Draft SMS updates must remain scheduled.',
            ], 422);
        }

        $resolved = $this->resolveRecipients($request->input('recipients'));

        if (empty($resolved['recipients'])) {
            return response()->json([
                'message' => 'No valid recipients found.',
            ], 422);
        }

        $this->smsService->updateScheduledDraft(
            message: $message,
            body: $request->input('body'),
            recipients: $resolved['recipients'],
            scheduledAt: \Carbon\Carbon::parse($request->input('scheduled_at')),
        );

        return response()->json([
            'status' => 1,
            'message' => 'Scheduled draft SMS updated successfully.',
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $message = SmsMessage::with(['recipients'])
            ->findOrFail($id);

        DB::transaction(function () use ($message) {
            $message->recipients()->delete();
            $message->delete();
        });

        return response()->json([
            'status' => 1,
            'message' => 'SMS record deleted successfully.',
        ]);
    }


    public function compose(): Response
    {
        $this->authorize('send-sms');

        $groups = ContactGroup::select(['id', 'name'])
            ->withCount('contacts')
            ->orderBy('name')
            ->get()
            ->map(fn ($g) => [
                'id'             => $g->id,
                'name'           => $g->name,
                'contacts_count' => $g->contacts_count,
            ]);

        return Inertia::render('sms/compose', [
            'groups' => $groups,
        ]);
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * Handle compose form submission.
     * Resolves all recipient types, dispatches jobs via SmsService.
     */
    public function send(SendSmsRequest $request): JsonResponse
    {
        $resolved = $this->resolveRecipients($request->input('recipients'));

        if (empty($resolved['recipients'])) {
            return response()->json([
                'message' => 'No valid recipients found.',
            ], 422);
        }

        $senderId = $request->user()->id;
        $body     = $request->input('body');

        if ($request->input('send_type') === 'scheduled') {
            $message = $this->smsService->scheduleRecipients(
                body:        $body,
                recipients:  $resolved['recipients'],
                senderId:    $senderId,
                scheduledAt: \Carbon\Carbon::parse($request->input('scheduled_at')),
            );

            return response()->json([
                'message'    => 'SMS scheduled successfully.',
                'message_id' => $message->id,
                'recipients' => count($resolved['recipients']),
                'scheduled'  => true,
            ]);
        }

        $message = $this->smsService->sendToRecipients(
            body:     $body,
            recipients:  $resolved['recipients'],
            senderId: $senderId,
        );

        return response()->json([
            'message'    => 'SMS queued successfully.',
            'message_id' => $message->id,
            'recipients' => count($resolved['recipients']),
            'scheduled'  => false,
        ]);
    }

    /**
     * Contact search endpoint for recipient autocomplete.
     * Returns max 15 results matching name or phone number.
     */
    public function searchContacts(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $contacts = Contact::select(['id', 'name', 'phone_number'])
            ->where(function ($query) use ($request) {
                $term = $request->input('q');
                $query->where('name', 'ilike', "%{$term}%")
                      ->orWhere('phone_number', 'ilike', "%{$term}%");
            })
            ->limit(15)
            ->get()
            ->map(fn ($c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'phone_number' => $c->phone_number,
                'label'        => "{$c->name} ({$c->phone_number})",
            ]);

        return response()->json($contacts);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /**
     * Resolve mixed recipient input into a flat array of phone numbers.
     *
     * Input shape:
     *   [
     *     { type: 'number',  value: '09171234567' },
     *     { type: 'contact', value: 5             },
     *     { type: 'group',   value: 2             },
     *   ]
     *
     * Returns unique recipients keyed by normalized phone number.
     */
    private function resolveRecipients(array $recipients): array
    {
        $resolvedRecipients = [];
        $contactInputs = [];
        $groupIds = [];

        foreach ($recipients as $recipient) {
            if (($recipient['type'] ?? null) === 'number' && ! empty($recipient['value'])) {
                $this->pushResolvedRecipient($resolvedRecipients, [
                    'phone_number' => $recipient['value'],
                    'contact_id' => null,
                ]);
                continue;
            }

            if (($recipient['type'] ?? null) === 'contact') {
                $contactId = (int) ($recipient['value'] ?? 0);

                if ($contactId > 0) {
                    $contactInputs[$contactId] = $recipient;
                }

                continue;
            }

            if (($recipient['type'] ?? null) === 'group') {
                $groupId = (int) ($recipient['value'] ?? 0);

                if ($groupId > 0) {
                    $groupIds[] = $groupId;
                }
            }
        }

        // Resolve individual contacts
        if (! empty($contactInputs)) {
            $dbContacts = Contact::whereIn('id', array_keys($contactInputs))
                ->get(['id', 'phone_number'])
                ->keyBy('id');

            foreach ($contactInputs as $contactId => $contactInput) {
                $dbContact = $dbContacts->get($contactId);

                if ($dbContact) {
                    $this->pushResolvedRecipient($resolvedRecipients, [
                        'phone_number' => $dbContact->phone_number,
                        'contact_id' => $dbContact->id,
                    ]);
                    continue;
                }

                if (! empty($contactInput['phone_number'])) {
                    $this->pushResolvedRecipient($resolvedRecipients, [
                        'phone_number' => $contactInput['phone_number'],
                        'contact_id' => null,
                    ]);
                }
            }
        }

        // Resolve group members
        if (! empty($groupIds)) {
            Contact::whereHas('groups', fn ($q) => $q->whereIn('contact_groups.id', $groupIds))
                ->get(['id', 'phone_number'])
                ->each(function ($contact) use (&$resolvedRecipients) {
                    $this->pushResolvedRecipient($resolvedRecipients, [
                        'phone_number' => $contact->phone_number,
                        'contact_id' => $contact->id,
                    ]);
                });
        }

        return [
            'recipients' => array_values($resolvedRecipients),
            'numbers' => array_column(array_values($resolvedRecipients), 'phone_number'),
        ];
    }

    private function pushResolvedRecipient(array &$resolvedRecipients, array $recipient): void
    {
        $normalizedNumber = $this->normalizeRecipientNumber($recipient['phone_number'] ?? null);

        if (! $normalizedNumber) {
            return;
        }

        $existing = $resolvedRecipients[$normalizedNumber] ?? null;

        $resolvedRecipients[$normalizedNumber] = [
            'phone_number' => $recipient['phone_number'],
            'contact_id' => $recipient['contact_id'] ?? ($existing['contact_id'] ?? null),
        ];
    }

    private function normalizeRecipientNumber(?string $number): ?string
    {
        if (! $number) {
            return null;
        }

        $stripped = preg_replace('/\D/', '', $number);

        if (str_starts_with($stripped, '63') && strlen($stripped) === 12) {
            return '0' . substr($stripped, 2);
        }

        if (strlen($stripped) === 10 && str_starts_with($stripped, '9')) {
            return '0' . $stripped;
        }

        return $stripped ?: null;
    }
}
