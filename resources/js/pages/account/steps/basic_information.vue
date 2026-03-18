<template>
    <div class="account-information d-flex flex-column flex-wrap gap-2">
        <div class="col-12">
            <h3 class="mb-4">Basic information</h3>
        </div>

        <div class="field d-flex justify-content-center mb-3">
            <ImageUpload
                :url="data.avatar"
                @setImage="setImage"
                :containerWidth=200
                :containerHeight=150
            />
        </div>

        <!-- User info -->
        <v-field as="div" class="field" slim name="first_name" vid="first_name" v-slot="{ errors }">
            <p-float-label variant="on">
                <p-input-text
                    id="first_name"
                    type="text"
                    v-model="data.first_name"
                    class="form-control shadow-none"
                    maxlength="50"
                    :class="{ 'p-invalid': errors[0] }"
                />
                <label for="first_name">First name</label>

            </p-float-label>
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" slim name="middle_name" vid="middle_name" v-slot="{ errors }">
            <p-float-label variant="on">
                <p-input-text
                    id="middle_name"
                    type="text"
                    v-model="data.middle_name"
                    class="form-control shadow-none"
                    maxlength="50"
                    :class="{ 'p-invalid': errors[0] }"
                />
                <label for="middle_name">Middle name</label>
            </p-float-label>
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" slim name="last_name" vid="last_name" v-slot="{ errors }">
            <p-float-label variant="on">
                <p-input-text
                    id="last_name"
                    type="text"
                    v-model="data.last_name"
                    class="form-control shadow-none"
                    maxlength="50"
                    :class="{ 'p-invalid': errors[0] }"
                />
                <label for="last_name">Last name</label>
            </p-float-label>
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" slim name="department_id" vid="department_id" v-slot="{ errors }">
            <label for="department_id">Department</label>
            <p-dropdown
                v-model="data.department_id"
                inputId="department_id"
                :options="departments"
                optionLabel="name"
                optionValue="id"
                class="w-full"
                :class="{ 'p-invalid': errors[0] }"
                placeholder="Select department"
            />
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" slim name="position_id" vid="position_id" v-slot="{ errors }">
            <label for="position_id">Position</label>
            <p-dropdown
                v-model="data.position_id"
                inputId="position_id"
                :options="availablePositions"
                optionLabel="name"
                optionValue="id"
                class="w-full"
                :class="{ 'p-invalid': errors[0] }"
                placeholder="Select position"
            />
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>
        
    </div>
</template>
<script>
    import ImageUpload from '../../../components/image-upload';
    import DepartmentServices from '../../../services/department';
    import PositionServices from '../../../services/position';

    export default {
        components : { ImageUpload },
        props: ['service'],

        data() {
            return {
                data:{
                    new_avatar: null,
                    avatar: null,
                    first_name: null,
                    middle_name: null,
                    last_name: null,
                    department_id: null,
                    position_id: null,
                },
                departments: [],
                positions: [],
            }
        },

        methods: {
            async loadDepartments() {
                try {
                    const response = await DepartmentServices.list({
                        page: 1,
                        limit: 100,
                    });

                    this.departments = response.data?.data?.data || [];
                } catch (error) {
                    console.log(error);
                }
            },

            async loadPositions() {
                try {
                    const response = await PositionServices.list({
                        page: 1,
                        limit: 100,
                    });

                    this.positions = response.data?.data?.data || [];
                } catch (error) {
                    console.log(error);
                }
            },

            fillInfo() {
                Object.entries(this.data).forEach((item) => {
                    let label = item[0];
                    this.data[label] = (label.includes('date') && this.service.data[label]) ? this.formatDate(this.parseDate(this.service.data[label])) : this.service.data[label];
                    
                });
            },

            setImage(base64) {
                this.data.image = base64;
                this.data.new_avatar = base64;
            }
        },

        mounted() {
            this.loadDepartments();
            this.loadPositions();
            this.fillInfo();
        },

        computed: {
            availablePositions() {
                if (!this.data.department_id) {
                    return this.positions;
                }

                return this.positions.filter((position) => position.department_id === this.data.department_id);
            }
        },

        watch: {
            'data.department_id': function (value) {
                if (!value) {
                    this.data.position_id = null;
                    return;
                }

                const isPositionValid = this.availablePositions.some((position) => position.id === this.data.position_id);

                if (!isPositionValid) {
                    this.data.position_id = null;
                }
            },

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
