<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * SendSmsRequest
 *
 * Validates the SMS compose form submission.
 *
 * recipients array accepts mixed input:
 *   - { type: 'number',  value: '09171234567' }
 *   - { type: 'contact', value: 1             }  (contact ID)
 *   - { type: 'group',   value: 3             }  (group ID)
 */
class SendSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('send-sms');
    }

    public function rules(): array
    {
        $rules = [
            'recipients'         => ['required', 'array', 'min:1'],
            'recipients.*.type'  => ['required', 'in:number,contact,group'],
            'recipients.*.value' => ['required'],
            'body'               => ['required', 'string', 'min:1', 'max:1600'],
            'send_type'          => ['required', 'in:immediate,scheduled'],
        ];

        $rules['scheduled_at'] = ['exclude_unless:send_type,scheduled', 'required', 'date', 'after:now'];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'recipients.required'        => 'At least one recipient is required.',
            'recipients.*.type.in'       => 'Invalid recipient type.',
            'body.required'              => 'Message body is required.',
            'body.max'                   => 'Message cannot exceed 160 characters.',
            'scheduled_at.required'      => 'Scheduled date and time is required.',
            'scheduled_at.after'         => 'Scheduled time must be in the future.',
        ];
    }
}
