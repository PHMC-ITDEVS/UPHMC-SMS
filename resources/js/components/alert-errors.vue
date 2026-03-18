<template>
    <div v-if="hasErrors" class="alert alert-danger mb-3">
        <p class="mb-2" v-text="message"></p>
        <ul class="mb-0 ps-3">
            <li v-for="(error, index) in errors" :key="index" v-text="error"></li>
        </ul>
    </div>
</template>

<script>
export default {
    name: 'AlertErrors',

    props: {
        form: {
            type: Object,
            required: true,
        },
        message: {
            type: String,
            default: 'There were some problems with your input.',
        },
    },

    computed: {
        errors() {
            const all = this.form?.errors?.all
            return typeof all === 'function' ? all.call(this.form.errors) : []
        },

        hasErrors() {
            const any = this.form?.errors?.any
            return typeof any === 'function' ? any.call(this.form.errors) : false
        },
    },
}
</script>
