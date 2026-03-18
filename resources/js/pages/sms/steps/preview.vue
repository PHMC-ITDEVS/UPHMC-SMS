<template>
    <div class="d-flex flex-column gap-3">
        <div class="border rounded-3 p-3 bg-light">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <div class="text-muted small mb-1">Delivery</div>
                    <div class="fw-semibold">{{ sendTypeLabel }}</div>
                </div>
                <div>
                    <div class="text-muted small mb-1">Recipients</div>
                    <div class="fw-semibold">{{ recipientCount }}</div>
                </div>
            </div>

            <div v-if="scheduledAt" class="mt-3">
                <div class="text-muted small mb-1">Scheduled At</div>
                <div class="fw-semibold">{{ scheduledAt }}</div>
            </div>
        </div>

        <div class="border rounded-3 p-3">
            <div class="text-muted small mb-2">SMS Preview</div>
            <div class="border rounded-3 px-3 py-2 bg-white">
                <div class="small text-muted mb-2">Message</div>
                <div class="mb-0" style="white-space: pre-wrap; word-break: break-word;">
                    {{ messageBody }}
                </div>
            </div>
            <div class="text-muted small mt-2">
                {{ characterCount }} character{{ characterCount !== 1 ? 's' : '' }}
            </div>
        </div>

        <div class="border rounded-3 p-3">
            <div class="text-muted small mb-2">Recipient List</div>
            <div v-if="recipients.length" class="d-flex flex-wrap gap-2">
                <span
                    v-for="(recipient, index) in recipients"
                    :key="`${recipient.type}-${recipient.value}-${index}`"
                    class="badge rounded-pill text-bg-light border"
                >
                    {{ recipientLabel(recipient) }}
                </span>
            </div>
            <div v-else class="text-muted small">
                No recipients selected.
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['service'],

    computed: {
        recipients() {
            return this.service.data.recipients || []
        },

        recipientCount() {
            return this.recipients.reduce((total, recipient) => {
                if (recipient.type === 'group') {
                    return total + (Number(recipient.contacts_count) || 0)
                }

                return total + 1
            }, 0)
        },

        messageBody() {
            return this.service.data.body || 'No message entered.'
        },

        characterCount() {
            return (this.service.data.body || '').length
        },

        sendTypeLabel() {
            return this.service.data.scheduled ? 'Scheduled SMS' : 'Send Immediately'
        },

        scheduledAt() {
            if (!this.service.data.scheduled_at) {
                return ''
            }

            return new Date(this.service.data.scheduled_at).toLocaleString('en-PH', {
                dateStyle: 'medium',
                timeStyle: 'short',
            })
        },

        recipientLabel() {
            return (recipient) => {
                if (recipient.type === 'group') {
                    const count = Number(recipient.contacts_count) || 0
                    return `${recipient.label || recipient.value} (${count} contact${count !== 1 ? 's' : ''})`
                }

                return recipient.label || recipient.value
            }
        },
    },
}
</script>
