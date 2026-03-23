<template>
    <div>
        <v-form ref="form" as="form">
            <app-wizard
                :states="states"
                :context="context"
                @onComplete="onComplete"
                :completeText="completeText()"
                @onNext="onNext"
                @onPrev="onPrev"
                :customStep="true"
            />
        </v-form>
    </div>
</template>
<script>
import { markRaw } from "vue";

import Information from './steps/information';
import ApiClientServices from '../../services/api-client';

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
                data: this.item,
            }),
            states: [
                {
                    title: 'Information',
                    view: markRaw(Information),
                }
            ]
        }
    },

    methods: {
        onNext() {
            if (this.context.loading) return;
            this.context.loading = true;
            ApiClientServices.validate(this.context.data)
            .then(() => {
                this.context.step_no++;
            })
            .catch((errors) => {
                try {
                    this.$refs.form.setErrors(this.getError(errors));
                }
                catch (ex) {}
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
            ApiClientServices.create(data)
            .then((response) => {
                this.$toast.add({ severity: 'success', summary: 'Success!', detail: response.data.message, life: 3000 });
                this.$emit('hideModal', response.data);
            })
            .catch((errors) => {
                try { this.$refs.form.setErrors(this.getError(errors)); }
                catch (ex) {}
            })
            .finally(() => {
                this.context.loading = false;
            });
        },

        updateData(data, id) {
            if (this.context.loading) return;
            this.context.loading = true;
            ApiClientServices.update(data, id)
            .then((response) => {
                this.$toast.add({ severity: 'success', summary: 'Success!', detail: response.data.message, life: 3000 });
                this.$emit('hideModal', response.data);
            })
            .catch((errors) => {
                try {
                    this.$refs.form.setErrors(this.getError(errors));
                }
                catch (ex) {}
            })
            .finally(() => {
                this.context.loading = false;
            });
        },

        completeText() {
            return (this.context.data.id) ? 'Save Changes' : 'Save';
        }
    },
}
</script>
