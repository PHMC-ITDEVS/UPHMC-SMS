<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\ApiRequestLog;
use App\Models\Contact;
use App\Models\SmsMessage;
use App\Services\Sms\SmsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmsApiController extends Controller
{
    public function __construct(private readonly SmsService $smsService) {}

    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:160'],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*.type' => ['required', 'string', 'in:number,contact,group'],
            'recipients.*.value' => ['required'],
            'send_type' => ['required', 'string', 'in:immediate,scheduled'],
            'scheduled_at' => ['exclude_unless:send_type,scheduled', 'required', 'date'],
        ]);

        if ($validator->fails()) {
            $response = [
                'status' => 0,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ];

            $this->logRequest($request, 422, $response);

            return response()->json($response, 422);
        }

        $resolved = $this->resolveRecipients($request->input('recipients'));

        if (empty($resolved['recipients'])) {
            $response = [
                'status' => 0,
                'message' => 'No valid recipients found.',
            ];

            $this->logRequest($request, 422, $response);

            return response()->json($response, 422);
        }

        $client = $this->client($request);

        if (! $client->created_by) {
            $response = [
                'status' => 0,
                'message' => 'API client is missing an owner account.',
            ];

            $this->logRequest($request, 422, $response);

            return response()->json($response, 422);
        }

        $serviceMethod = $request->input('send_type') === 'scheduled'
            ? 'scheduleRecipients'
            : 'sendToRecipients';

        $args = [
            'body' => $request->input('body'),
            'recipients' => $resolved['recipients'],
            'senderId' => $client->created_by,
            'apiClientId' => $client->id,
            'source' => 'api',
        ];

        if ($serviceMethod === 'scheduleRecipients') {
            $args['scheduledAt'] = Carbon::parse($request->input('scheduled_at'));
        }

        $message = $this->smsService->{$serviceMethod}(...$args);

        $response = [
            'status' => 1,
            'message' => $serviceMethod === 'scheduleRecipients'
                ? 'SMS scheduled successfully.'
                : 'SMS queued successfully.',
            'data' => [
                'message_id' => $message->id,
                'source' => 'api',
                'scheduled' => $serviceMethod === 'scheduleRecipients',
                'recipients' => count($resolved['recipients']),
            ],
        ];

        $this->logRequest($request, 200, $response);

        return response()->json($response, 200);
    }

    public function status(Request $request, $id): JsonResponse
    {
        $message = SmsMessage::with(['recipients'])
            ->where('api_client_id', $this->client($request)->id)
            ->findOrFail($id);

        $response = [
            'status' => 1,
            'message' => 'SMS status fetched successfully.',
            'data' => [
                'message_id' => $message->id,
                'type' => $message->type,
                'status' => $message->status instanceof \BackedEnum ? $message->status->value : (string) $message->status,
                'source' => $message->source,
                'total_recipients' => $message->total_recipients,
                'sent_count' => $message->sent_count,
                'failed_count' => $message->failed_count,
                'scheduled_at' => $message->scheduled_at,
                'created_at' => $message->created_at,
            ],
        ];

        $this->logRequest($request, 200, $response);

        return response()->json($response, 200);
    }

    protected function client(Request $request): ApiClient
    {
        return $request->attributes->get('api_client');
    }

    protected function logRequest(Request $request, int $responseCode, array $summary): void
    {
        $client = $this->client($request);

        ApiRequestLog::create([
            'api_client_id' => $client->id,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'request_payload' => $request->except(['body']),
            'response_code' => $responseCode,
            'response_summary' => $summary,
        ]);
    }

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
                }
            }
        }

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
