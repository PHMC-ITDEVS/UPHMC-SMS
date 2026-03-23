<template>
    <div class="api-client-information d-flex flex-column flex-wrap">
        <v-field as="div" class="field" slim name="name" vid="name" v-slot="{ errors }">
            <p-float-label variant="on">
                <p-input-text
                    type="text"
                    v-model="data.name"
                    class="form-control shadow-none"
                    maxlength="100"
                    :class="{ 'p-invalid': errors[0] }"
                />
                <label for="name">Name</label>
            </p-float-label>
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" slim name="department_id" vid="department_id" v-slot="{ errors }">
            <p-float-label variant="on">
                <p-select
                    v-model="data.department_id"
                    :options="departments"
                    optionLabel="name"
                    optionValue="id"
                    showClear
                    class="form-control shadow-none"
                    :class="{ 'p-invalid': errors[0] }"
                />
                <label for="department_id">Department Scope</label>
            </p-float-label>
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" slim name="status" vid="status" v-slot="{ errors }">
            <p-float-label variant="on">
                <p-select
                    v-model="data.status"
                    :options="statuses"
                    optionLabel="label"
                    optionValue="value"
                    class="form-control shadow-none"
                    :class="{ 'p-invalid': errors[0] }"
                />
                <label for="status">Status</label>
            </p-float-label>
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" slim name="allowed_ips" vid="allowed_ips" v-slot="{ errors }">
            <p-float-label variant="on">
                <p-textarea
                    v-model="data.allowed_ips"
                    rows="3"
                    cols="30"
                    style="resize: none"
                    class="form-control w-full"
                    :class="{ 'p-invalid': errors[0] }"
                />
                <label for="allowed_ips">Allowed IPs</label>
            </p-float-label>
            <small class="text-muted d-block">One per line or comma-separated. Leave blank to allow any IP.</small>
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" slim name="description" vid="description" v-slot="{ errors }">
            <p-float-label variant="on">
                <p-textarea
                    v-model="data.description"
                    rows="3"
                    cols="30"
                    style="resize: none"
                    class="form-control w-full"
                    :class="{ 'p-invalid': errors[0] }"
                />
                <label for="description">Description</label>
            </p-float-label>
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>
    </div>
</template>
<script>
import DepartmentServices from '../../../services/department';

export default {
    props: ['service'],

    data() {
        return {
            departments: [],
            statuses: [
                { label: 'Active', value: 'active' },
                { label: 'Inactive', value: 'inactive' },
            ],
            data: {
                name: null,
                department_id: null,
                status: 'active',
                allowed_ips: null,
                description: null,
            },
        }
    },

    mounted() {
        this.fillInfo();
        this.loadDepartments();
    },

    methods: {
        fillInfo() {
            Object.entries(this.data).forEach((item) => {
                let label = item[0];
                this.data[label] = this.service.data[label];
            });
        },

        loadDepartments() {
            DepartmentServices.list({
                page: 1,
                limit: 999,
            })
            .then((response) => {
                this.departments = response.data.data.data || [];
            })
            .catch(() => {});
        },
    },

    watch: {
        data: {
            handler: function () {
                Object.entries(this.data).forEach((item) => {
                    let label = item[0];
                    this.service.data[label] = this.data[label];
                });
            },
            deep: true,
            immediate: false
        }
    }
}
</script>
