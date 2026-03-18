<template>
    <div>
        <v-form ref="form" as="form">
            <app-wizard
                :states="states"
                :context="context"
                @onComplete="onComplete"
                completeText="Send"
                @onNext="onNext"
                @onPrev="onPrev"
                :customStep="true"
                :nextText="'Next & Preview'"
            />
        </v-form>
    </div>
</template>
<script>
    import { markRaw } from "vue";
    
    import SmsCreation from './steps/sms_creation';
    import SmsPreview from './steps/preview';
    import SmsServices from '../../services/sms';

    export default {
        emits: ['hideModal'],
        props: ['item'],

        data() {
            return {
                context: new Form({
                    loading: !1,
                    id: !this.item ? 0 : this.item.id,
                    step_no: 0,
                    disable_btn: !0,
                    data : this.item,
                }),
                states: [
                    
                    {
                        title : 'Preview',
                        view: markRaw(SmsPreview),
                    }
                ]
            }
        },

        mounted() {
            this.changePage();
        },

        methods: {
            changePage() {
                let page_type = this.context.data.page_type;

                if(page_type != 'view') {
                    this.states.unshift({
                        title : 'Create SMS',
                        view: markRaw(SmsCreation),
                    });
                }
            },

            onNext() {
                if (this.context.loading) return;
                this.context.loading = true;
                SmsServices.validate(this.context.data)
                .then((response) => {
                    this.context.step_no++;
                })
                .catch((errors) => {
                    try { 
                        this.$refs.form.setErrors(this.getError(errors));
                    }
                    catch(ex){ console.log(ex)}
                })
                .finally(() => {
                    this.context.loading = false;
                });
                
            },

            onPrev() {
                if (this.context.loading) return;
                this.context.step_no--;
            },

            onComplete(ctx) {
                (ctx.data.id) ? this.updateData(ctx.data, ctx.data.id) : this.createData(ctx.data);
            },

            createData(data) {
                if (this.context.loading) return;
                this.context.loading = true;
                SmsServices.create(data)
                .then((response) => {
                    this.$toast.add({ severity: 'success', summary: 'Success!', detail: response.data.message, life: 3000 });
                    this.$emit('hideModal');
                })
                .catch((errors) => {
                    try { this.$refs.form.setErrors(this.getError(errors)); }
                    catch(ex){ console.log(ex) }
                })
                .finally(() => {
                    this.context.loading = false;
                });
            },

            updateData(data, id) {
                if (this.context.loading) return;
                this.context.loading = true;
                SmsServices.update(data,id)
                .then((response) => {
                    this.$toast.add({ severity: 'success', summary: 'Success!', detail: response.data.message, life: 3000 });
                    this.$emit('hideModal');
                })
                .catch((errors) => {
                    try { 
                        this.$refs.form.setErrors(this.getError(errors));
                    }
                    catch(ex){ console.log(ex)}
                })
                .finally(() => {
                    this.context.loading = false;
                });
            },
        },

        computed: {
        },

        watch: {
        }
    }
</script>