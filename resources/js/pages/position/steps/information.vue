<template>
    <div class="position-information d-flex flex-column flex-wrap">
        <v-field as="div" class="field" slim name="department_id" vid="department_id" v-slot="{ errors }">
            <label for="department_id">Department</label>
            <p-dropdown
                v-model="data.department_id"
                inputId="department_id"
                :options="departments"
                optionLabel="name"
                optionValue="id"
                class="w-full"
                :loading="departmentsLoading"
                :class="{ 'p-invalid': errors[0] }"
                @show="ensureDepartmentsLoaded"
            />
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

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

        <v-field as="div" class="field" slim name="description" vid="description" v-slot="{ errors }">
            <p-float-label variant="on">
                <label for="description">Description</label>
                <p-textarea
                    v-model="data.description"
                    rows="3"
                    cols="30"
                    style="resize: none"
                    :class="{ 'p-invalid': errors[0] }"
                    class="form-control w-full"
                />
            </p-float-label>
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>
    </div>
</template>
<script>
    import DepartmentServices from '../../../services/department'

    export default {
        props: ['service'],

        data() {
            return {
                data: {
                    department_id: null,
                    name: null,
                    description: null,
                },
                departments: [],
                departmentsLoading: false,
            }
        },

        methods: {
            async ensureDepartmentsLoaded() {
                if (this.departments.length || this.departmentsLoading) return;

                this.departmentsLoading = true;

                try {
                    const response = await DepartmentServices.list({
                        page: 1,
                        limit: 100,
                    });

                    this.departments = response.data?.data?.data || [];
                } catch (error) {
                    console.log(error);
                } finally {
                    this.departmentsLoading = false;
                }
            },

            fillInfo() {
                Object.entries(this.data).forEach((item) => {
                    let label = item[0];
                    this.data[label] = (label.includes('date') && this.service.data[label]) ? this.formatDate(this.parseDate(this.service.data[label])) : this.service.data[label];
                });
            },
        },

        mounted() {
            this.ensureDepartmentsLoaded();
            this.fillInfo();
        },

        watch: {
            data: {
                handler: function () {
                    Object.entries(this.data).forEach((item) => {
                        let label = item[0];
                        this.service.data[label] = label.includes('date') ? this.formatDate(this.parseDate(this.service.data[label])) : this.data[label];
                    });
                },
                deep: true,
                immediate: false
            }
        }
    }
</script>
