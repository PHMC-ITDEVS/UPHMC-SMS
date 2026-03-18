<template>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Recipients
            <span class="text-gray-400 font-normal ml-1">
                ({{ recipientCount }} recipient{{ recipientCount !== 1 ? 's' : '' }} -
                {{ recipientCount > 1 ? 'Bulk' : 'Single' }})
            </span>
        </label>

        <p-autocomplete
            ref="autocomplete"
            v-model="selectedRecipients"
            inputId="recipient-autocomplete"
            multiple
            fluid
            dropdown
            dropdownMode="blank"
            @complete="searchContacts"
            :suggestions="suggestions"
            optionLabel="label"
            placeholder="Search..."
            class="w-full sms-recipient-autocomplete"
            :class="{ 'p-invalid': !!error }"
            :loading="loading"
            :virtualScrollerOptions="virtualScrollerOptions"
            @item-select="handleSelect"
            @keydown.enter.prevent="addTypedNumber()"
            @keydown.backspace="removeLastRecipient"
        >
            <template #option="{ option }">
                <div
                    v-if="option.type === 'number'"
                    class="px-1 py-1 text-sm text-green-700 font-medium"
                >
                    + Add "{{ option.label }}" as number
                </div>

                <div
                    v-else-if="option.type === 'group'"
                    class="d-flex flex-row align-items-center gap-2 px-1 py-1"
                >
                    <span class="mdi mdi-account-group fs-30px"></span>
                    <div>
                        <div class="font-medium text-gray-800">{{ option.name }}</div>
                        <div class="text-gray-400 text-xs">{{ option.contacts_count }} contact(s)</div>
                    </div>
                </div>

                <div v-else class="d-flex flex-row align-items-center gap-2 px-1 py-1">
                    <img :src="option?.avatar" :alt="option.name" width="30" height="30"/>
                    <div>
                        <div class="font-medium text-gray-800">{{ option.name }}</div>
                        <div class="text-gray-400 text-xs">{{ option.phone_number }}</div>
                    </div>
                </div>
            </template>

            <template #empty>
                <div class="px-3 py-2 text-sm text-gray-400">
                    No contacts found. Press Enter to add as number.
                </div>
            </template>
        </p-autocomplete>

        <span class="text-muted small">Hint: Type Group, Seach Contacts, or Type Number</span>

        <p v-if="error" class="text-danger text-xs mt-1">
            {{ error }}
        </p>
        <p v-if="numberError" class="text-danger text-xs mt-1">
            {{ numberError }}
        </p>

        <!-- <div class="mt-3">
            <label class="block text-xs font-medium text-gray-500 mb-1">
                Or add entire group:
            </label>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="group in availableGroups"
                    :key="group.id"
                    type="button"
                    class="text-xs px-3 py-1.5 rounded-full border transition-colors"
                    :class="isGroupSelected(group.id)
                        ? 'bg-blue-600 text-white border-blue-600'
                        : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400'"
                    @click="toggleGroup(group)"
                >
                    {{ group.name }}
                    <span class="opacity-60">({{ group.contacts_count }})</span>
                </button>
            </div>
        </div> -->
    </div>
</template>

<script>
import axios from 'axios'

export default {
    name: 'SmsRecipientPicker',

    props: {
        modelValue: {
            type: Array,
            default: () => [],
        },
        groups: {
            type: Array,
            default: () => [],
        },
        error: {
            type: String,
            default: '',
        },
    },

    emits: ['update:modelValue'],

    data() {
        return {
            suggestions: [],
            loading: false,
            searchQuery: '',
            numberError: '',
            localGroups: [],
            searchPage: 0,
            searchTotalPages: 1,
            searchLimit: 20,
            searchRequestKey: 0,
        }
    },

    computed: {
        selectedRecipients: {
            get() {
                return this.modelValue
            },
            set(value) {
                this.$emit('update:modelValue', this.dedupeRecipients(value))
            },
        },

        recipientCount() {
            return this.selectedRecipients.length
        },

        availableGroups() {
            return Array.isArray(this.groups) && this.groups.length
                ? this.groups
                : this.localGroups
        },

        virtualScrollerOptions() {
            return {
                onScrollIndexChange: this.onSuggestionScrollIndexChange,
                itemSize: 46,
                showLoader: false,
                delay: 100,
            }
        },
    },

    methods: {
        async ensureGroupsLoaded() {
            if (this.availableGroups.length) return

            try {
                const response = await axios.get(route('contact_group.list'), {
                    params: {
                        page: 1,
                        limit: 100,
                    },
                })

                this.localGroups = response.data?.data?.data || []
            } catch (error) {
                this.localGroups = []
            }
        },

        async searchContacts(event) {
            const query = (event.query || '').trim()
            this.searchQuery = query
            this.searchPage = 0
            this.searchTotalPages = 1

            await this.ensureGroupsLoaded()

            await this.fetchSuggestionPage(1, { reset: true })
        },

        async fetchSuggestionPage(page = 1, { reset = false } = {}) {
            if ((!reset && this.loading) || page > this.searchTotalPages) return

            const query = this.searchQuery.trim()
            const groupMode = this.isGroupQuery(query)
            const groupQuery = this.extractGroupQuery(query)
            const requestKey = ++this.searchRequestKey

            if (reset) {
                this.suggestions = []
            }

            if (groupMode) {
                const groups = this.getMatchingGroups(groupQuery)
                this.suggestions = groups
                this.searchPage = 1
                this.searchTotalPages = 1
                this.loading = false
                return
            }

            this.loading = true

            try {
                const response = await axios.get(route('phonebook.list'), {
                    params: {
                        search: query || undefined,
                        page,
                        limit: this.searchLimit,
                    },
                })

                if (requestKey !== this.searchRequestKey) return

                const payload = response.data?.data || {}
                const contacts = (payload.data || [])
                    .map(this.mapContactToRecipient)
                    .filter(item => !this.isAlreadyAdded(item.type, item.value))
                const groups = reset
                    ? this.getMatchingGroups(query)
                    : []

                const numberOption = reset && this.isValidNumber(query) && !this.isAlreadyAdded('number', query)
                    ? [this.buildNumberRecipient(query)]
                    : []

                this.suggestions = reset
                    ? [...groups, ...contacts, ...numberOption]
                    : [
                        ...this.suggestions,
                        ...contacts.filter(item => !this.suggestions.some(existing => existing.type === item.type && existing.value === item.value))
                    ]

                this.searchPage = payload.current_page ?? page
                this.searchTotalPages = payload.last_page ?? page
            } catch (error) {
                if (requestKey !== this.searchRequestKey) return

                this.suggestions = reset && this.isValidNumber(query) && !this.isAlreadyAdded('number', query)
                    ? [this.buildNumberRecipient(query)]
                    : this.suggestions
            } finally {
                if (requestKey === this.searchRequestKey) {
                    this.loading = false
                }
            }
        },

        onSuggestionScrollIndexChange(event) {
            if (this.loading || this.searchPage >= this.searchTotalPages) {
                return
            }

            const lastVisibleIndex = event.last ?? 0

            if (lastVisibleIndex >= this.suggestions.length - 1) {
                this.fetchSuggestionPage(this.searchPage + 1)
            }
        },

        handleSelect() {
            // Let PrimeVue finish committing the selection first, then clear
            this.$nextTick(() => {
                this.clearSearchState()
            })
        },

        addTypedNumber() {
            const number = this.searchQuery.trim()
            this.numberError = ''

            if (!number) return

            if (!this.isValidNumber(number)) {
                this.numberError = 'Invalid number. Must start with 09 (11 digits) or 639 (12 digits).'
                return
            }

            if (this.isAlreadyAdded('number', number)) {
                this.numberError = 'This number has already been added.'
                return
            }

            this.selectedRecipients = [
                ...this.selectedRecipients,
                this.buildNumberRecipient(number),
            ]

            this.clearSearchState()
        },

        removeLastRecipient() {
            if (this.searchQuery || this.selectedRecipients.length === 0) {
                return
            }

            this.selectedRecipients = this.selectedRecipients.slice(0, -1)
        },

        toggleGroup(group) {
            const recipients = [...this.selectedRecipients]
            const index = recipients.findIndex(item => item.type === 'group' && item.value === group.id)

            if (index !== -1) {
                recipients.splice(index, 1)
            } else {
                recipients.push({
                    type: 'group',
                    value: group.id,
                    label: `Group: ${group.name}`,
                    contacts_count: group.contacts_count ?? 0,
                })
            }

            this.selectedRecipients = recipients
            this.focusInput()
        },

        isGroupSelected(groupId) {
            return this.selectedRecipients.some(item => item.type === 'group' && item.value === groupId)
        },

        isAlreadyAdded(type, value) {
            return this.selectedRecipients.some(item => item.type === type && item.value === value)
        },

        isValidNumber(value) {
            if (!value || typeof value !== 'string') return false
            const cleaned = value.replace(/[\s\-().]/g, '')

            if (cleaned.startsWith('09')) return cleaned.length === 11
            if (cleaned.startsWith('639')) return cleaned.length === 12

            return false
        },

        buildNumberRecipient(number) {
            return {
                type: 'number',
                value: number.trim(),
                label: number.trim(),
            }
        },

        isGroupQuery(query) {
            return query.toLowerCase().startsWith('group:')
        },

        extractGroupQuery(query) {
            if (!this.isGroupQuery(query)) return query

            return query.slice(6).trim()
        },

        mapContactToRecipient(contact) {
            return {
                type: 'contact',
                value: contact.id,
                label: `${contact.name} (${contact.phone_number})`,
                name: contact.name,
                phone_number: contact.phone_number,
                avatar: contact.avatar,
            }
        },

        mapGroupToRecipient(group) {
            return {
                type: 'group',
                value: group.id,
                label: `Group: ${group.name}`,
                name: group.name,
                contacts_count: group.contacts_count ?? 0,
            }
        },

        getMatchingGroups(query) {
            const term = query.trim().toLowerCase()

            return (this.availableGroups || [])
                .filter(group => !this.isAlreadyAdded('group', group.id))
                .filter(group => {
                    if (!term) return true

                    return group.name?.toLowerCase().includes(term)
                })
                .map(this.mapGroupToRecipient)
        },

        dedupeRecipients(items) {
            const seen = new Set()
            return (items || []).filter(item => {
                const key = `${item.type}:${item.value}`
                if (seen.has(key)) return false
                seen.add(key)
                return true
            })
        },

        clearSearchState() {
            this.searchQuery = '';
            this.suggestions = [];
            this.numberError = '';
            this.searchPage = 0;
            this.searchTotalPages = 1;
            this.loading = false;
            this.searchRequestKey += 1;

            this.$nextTick(() => {
                const input = this.$refs.autocomplete?.$el?.querySelector('input')
                if (input) {
                    input.value = ''
                    input.focus()
                }
            })
        },

        focusInput() {
            const input = this.$refs.autocomplete?.$el?.querySelector('input')
            if (input) input.focus()
        },
    },

    mounted() {
        this.ensureGroupsLoaded()
    },
}
</script>
