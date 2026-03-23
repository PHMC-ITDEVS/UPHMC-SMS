<template>
    <div class="sms-infomation d-flex flex-column gap-2">
        <v-field as="div" class="field" slim name="recipients"  v-slot="{ errors }">
            <app-sms-recipient-picker
                v-model="data.recipients"
                :error="errors[0]"
                :groups="data.groups"
            />
        </v-field>
        <div class="d-flex flex-row align-items-center gap-2 w-full">
            <v-field as="div" class="field" slim name="scheduled">
                <div class="d-flex align-items-center gap-2">
                    <p-checkbox 
                        binary 
                        v-model="data.scheduled" 
                        class="p-checkbox-sm"
                        @change="data.scheduled_at = null"
                    ></p-checkbox>
                    <label for="scheduled" class="mb-0 text-nowrap">Schedule Message</label>
                </div>
                
            </v-field>  

            <v-field as="div" class="field w-100" slim name="scheduled_at" rules="required" v-if="data.scheduled" v-slot="{ errors }">
                <p-float-label variant="on">
                    <p-datepicker 
                        v-model="data.scheduled_at" 
                        class="p-input-sm form-control shadow-none p-0"
                        :min-date="new Date()"
                        :class="{ 'p-invalid': errors[0] }"
                        hourFormat="12"
                        showTime
                    />
                    <label for="scheduled_at">Scheduled Date</label>
                </p-float-label>
                <small class="p-error">{{ errors[0] }}</small>
            </v-field>  
        </div>
        

        <v-field as="div" class="field" slim rules="required" name="body" v-slot="{ errors }">
            <p-float-label variant="on">
                <p-textarea 
                    v-model="data.body" 
                    rows="4" 
                    cols="30" 
                    style="resize: none" 
                    class="form-control shadow-none"
                    :class="{ 'p-invalid': errors[0] }"
                />
                <label for="Message">Compose Message</label>
            </p-float-label>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <small class="text-muted">
                    Spaces and line breaks are counted as part of the message.
                </small>
                <small class="text-muted">
                    {{ characterCount }} character{{ characterCount !== 1 ? 's' : '' }}
                </small>
            </div>
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>
        
    </div>
</template>
<script>

    export default {
        props: ['service'],

        data() {
            return {
                data:{
                    recipients: [],
                    groups: [],
                    scheduled: false,
                    body: null,
                    send_type: 'immediate',
                    scheduled_at: null,
                }
            }
        },

        methods: {
            fillInfo() {
                Object.keys(this.data).forEach((label) => {
                    if (label === 'scheduled_at') {
                        this.data.scheduled_at = this.service.data.scheduled_at
                            ? this.parseDate(this.service.data.scheduled_at, 3)
                            : null;
                        return;
                    }

                    this.data[label] = this.service.data[label];
                });

                this.data.scheduled = this.data.send_type === 'scheduled';
            },
        },

        computed: {
            characterCount() {
                return (this.data.body || '').length;
            },
        },

        mounted() {
            this.fillInfo();
        },

        watch: {
            'data.scheduled': {
                handler(value) {
                    this.data.send_type = value ? 'scheduled' : 'immediate'
                },
                immediate: true,
            },

            data: {
                handler: function () {
                    Object.keys(this.data).forEach((label) => {
                        if (label === 'scheduled_at') {
                            this.service.data.scheduled_at = this.data.scheduled_at
                                ? [
                                    this.data.scheduled_at.getFullYear(),
                                    String(this.data.scheduled_at.getMonth() + 1).padStart(2, '0'),
                                    String(this.data.scheduled_at.getDate()).padStart(2, '0'),
                                ].join('-') + ' ' + [
                                    String(this.data.scheduled_at.getHours()).padStart(2, '0'),
                                    String(this.data.scheduled_at.getMinutes()).padStart(2, '0'),
                                    String(this.data.scheduled_at.getSeconds()).padStart(2, '0'),
                                ].join(':')
                                : null;
                            return;
                        }

                        this.service.data[label] = this.data[label];
                    });
                },
                deep: true,
                immediate: false
            }
        }
    }
</script>
