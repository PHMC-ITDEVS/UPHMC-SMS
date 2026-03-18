<template>
    <app-layout title="Compose SMS">
        <div class="max-w-2xl mx-auto py-8 px-4">

            <h1 class="text-2xl font-bold text-gray-800 mb-6">Compose SMS</h1>

            <form @submit.prevent="openPreview" class="bg-white rounded-xl shadow p-6 space-y-6">

                <!-- Send Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Send Type
                    </label>
                    <select
                        v-model="form.send_type"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="immediate">Send Immediately</option>
                        <option value="scheduled">Schedule for Later</option>
                    </select>
                </div>

                <!-- Scheduled At -->
                <div v-if="form.send_type === 'scheduled'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Schedule Date & Time
                    </label>
                    <input
                        v-model="form.scheduled_at"
                        type="datetime-local"
                        :min="minScheduledAt"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{ 'border-red-400': errors.scheduled_at }"
                    />
                    <p v-if="errors.scheduled_at" class="text-red-500 text-xs mt-1">
                        {{ errors.scheduled_at }}
                    </p>
                </div>

                <!-- Recipients -->
                <sms-recipient-picker
                    v-model="form.recipients"
                    :groups="groups"
                    :error="errors.recipients"
                />

                <!-- Message Body -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Message
                    </label>
                    <textarea
                        v-model="form.body"
                        rows="4"
                        maxlength="160"
                        placeholder="Type your message here..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{ 'border-red-400': errors.body }"
                    />
                    <div class="flex justify-between mt-1">
                        <p v-if="errors.body" class="text-red-500 text-xs">{{ errors.body }}</p>
                        <span
                            class="text-xs ml-auto"
                            :class="charCount >= 150 ? 'text-red-500 font-semibold' : 'text-gray-400'"
                        >
                            {{ charCount }}/160
                        </span>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="!canSubmit"
                        class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        Preview &amp; Send
                    </button>
                </div>

            </form>
        </div>

        <!-- Preview / Confirm Dialog -->
        <teleport to="body">
            <div
                v-if="showPreview"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4"
                @click.self="showPreview = false"
            >
                <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">

                    <h2 class="text-lg font-bold text-gray-800">Confirm Send</h2>

                    <div class="space-y-3 text-sm">
                        <div class="flex gap-2">
                            <span class="font-medium text-gray-500 w-24 shrink-0">Type:</span>
                            <span class="text-gray-800 capitalize">
                                {{ form.send_type === 'scheduled' ? 'Scheduled' : 'Immediately' }}
                            </span>
                        </div>

                        <div v-if="form.send_type === 'scheduled'" class="flex gap-2">
                            <span class="font-medium text-gray-500 w-24 shrink-0">Send At:</span>
                            <span class="text-gray-800">{{ formattedScheduledAt }}</span>
                        </div>

                        <div class="flex gap-2">
                            <span class="font-medium text-gray-500 w-24 shrink-0">Recipients:</span>
                            <span class="text-gray-800">{{ recipientCount }} recipient{{ recipientCount !== 1 ? 's' : '' }}</span>
                        </div>

                        <div class="flex gap-2">
                            <span class="font-medium text-gray-500 w-24 shrink-0">Message:</span>
                            <span class="text-gray-800 whitespace-pre-wrap break-words">{{ form.body }}</span>
                        </div>
                    </div>

                    <div class="flex gap-3 justify-end pt-2">
                        <button
                            type="button"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
                            @click="showPreview = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="sending"
                            class="px-5 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="confirmSend"
                        >
                            {{ sending ? 'Sending...' : 'Confirm Send' }}
                        </button>
                    </div>
                </div>
            </div>
        </teleport>

    </app-layout>
</template>

<script>
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import SmsRecipientPicker from '@/components/sms-recipient-picker.vue'

export default {
    name: 'SmsCompose',

    components: {
        SmsRecipientPicker,
    },

    props: {
        groups: {
            type: Array,
            default: () => [],
        },
    },

    data() {
        return {
            form: {
                send_type:    'immediate',
                scheduled_at: '',
                recipients:   [],   // [{ type, value, label }]
                body:         '',
            },

            errors: {
                recipients:   '',
                body:         '',
                scheduled_at: '',
            },

            // Dialog state
            showPreview: false,
            sending:     false,
        }
    },

    computed: {
        charCount() {
            return this.form.body.length
        },

        recipientCount() {
            return this.form.recipients.reduce((total, recipient) => {
                if (recipient.type === 'group') {
                    return total + (Number(recipient.contacts_count) || 0)
                }

                return total + 1
            }, 0)
        },

        canSubmit() {
            return (
                this.form.recipients.length > 0 &&
                this.form.body.trim().length > 0 &&
                (this.form.send_type === 'immediate' || this.form.scheduled_at !== '')
            )
        },

        minScheduledAt() {
            // datetime-local min = now + 5 minutes
            const d = new Date(Date.now() + 5 * 60 * 1000)
            return d.toISOString().slice(0, 16)
        },

        formattedScheduledAt() {
            if (! this.form.scheduled_at) return ''
            return new Date(this.form.scheduled_at).toLocaleString('en-PH', {
                dateStyle: 'medium',
                timeStyle: 'short',
            })
        },
    },

    methods: {
        // ── Form Submit ───────────────────────────────────────────────────────

        openPreview() {
            this.clearErrors()

            if (this.form.recipients.length === 0) {
                this.errors.recipients = 'At least one recipient is required.'
                return
            }

            if (this.form.body.trim().length === 0) {
                this.errors.body = 'Message body is required.'
                return
            }

            if (this.form.send_type === 'scheduled' && ! this.form.scheduled_at) {
                this.errors.scheduled_at = 'Please select a scheduled date and time.'
                return
            }

            this.showPreview = true
        },

        async confirmSend() {
            this.sending = true

            try {
                const payload = {
                    send_type:  this.form.send_type,
                    recipients: this.form.recipients.map(r => ({
                        type:  r.type,
                        value: r.value,
                    })),
                    body: this.form.body,
                }

                if (this.form.send_type === 'scheduled') {
                    payload.scheduled_at = this.form.scheduled_at
                }

                const response = await axios.post(route('sms.send'), payload)

                this.showPreview = false
                this.resetForm()

                router.visit(route('sms.compose'), {
                    preserveScroll: true,
                    onSuccess: () => {
                        // Flash handled by AppLayout
                    },
                })

                // Notify success — adjust to your notification system
                alert(response.data.message)

            } catch (error) {
                if (error.response?.status === 422) {
                    const serverErrors = error.response.data.errors ?? {}
                    Object.keys(serverErrors).forEach(key => {
                        if (this.errors.hasOwnProperty(key)) {
                            this.errors[key] = serverErrors[key][0]
                        }
                    })
                    this.showPreview = false
                } else {
                    alert('Something went wrong. Please try again.')
                }
            } finally {
                this.sending = false
            }
        },

        // ── Helpers ───────────────────────────────────────────────────────────

        clearErrors() {
            this.errors = { recipients: '', body: '', scheduled_at: '' }
        },

        resetForm() {
            this.form = {
                send_type:    'immediate',
                scheduled_at: '',
                recipients:   [],
                body:         '',
            }
            this.clearErrors()
        },
    },
}
</script>
