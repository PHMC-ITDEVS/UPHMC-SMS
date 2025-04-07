<template>
    <div>
        <div class="col-12">
            <h3 class="mb-4">Basic information</h3>
        </div>

        <div class="field d-flex justify-content-center mb-3">
            <ImageUpload
                :url="image"
                @setImage="setImage"
                :containerWidth=200
                :containerHeight=150
            />
        </div>

        <!-- User info -->
        <v-field as="div" class="field" slim name="first_name" vid="first_name" v-slot="{ errors }">
            <span class="p-float-label">
                <p-input-text
                    id="first_name"
                    type="text"
                    v-model="first_name"
                    class="form-control shadow-none"
                    maxlength="50"
                    :class="{ 'p-invalid': errors[0] }"
                />
                <label for="first_name">First name</label>
            </span>
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" slim name="middle_name" vid="middle_name" v-slot="{ errors }">
            <span class="p-float-label">
                <p-input-text
                    id="middle_name"
                    type="text"
                    v-model="middle_name"
                    class="form-control shadow-none"
                    maxlength="50"
                    :class="{ 'p-invalid': errors[0] }"
                />
                <label for="middle_name">Middle name</label>
            </span>
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" slim name="last_name" vid="last_name" v-slot="{ errors }">
            <span class="p-float-label">
                <p-input-text
                    id="last_name"
                    type="text"
                    v-model="last_name"
                    class="form-control shadow-none"
                    maxlength="50"
                    :class="{ 'p-invalid': errors[0] }"
                />
                <label for="last_name">Last name</label>
            </span>
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>
        
    </div>
</template>
<script>
    import ImageUpload from '../../../components/image-upload';

    export default {
        components : { ImageUpload },
        props: ['service'],

        data() {
            return {
                image: '',
                username: '',
                password: '',
                confirm_password: '',
                first_name: '',
                middle_name: '',
                last_name: '',
                email: '',
                role: 'DSP'
            }
        },

        methods: {
            fillInfo() {
                this.image = this.service.image;
                this.username = this.service.username;
                this.password = this.service.password;
                this.confirm_password = this.service.confirm_password;
                this.first_name = this.service.first_name;
                this.middle_name = this.service.middle_name;
                this.last_name = this.service.last_name;
                this.email = this.service.email;
                this.role = this.service.role;
            },

            setImage(base64) {
                this.image = base64;
            }
        },

        mounted() {
            this.fillInfo();
        },

        watch: {
            image() {
                this.service.image = this.image;
            },

            username() {
                this.service.username = this.username;
            },

            password() {
                this.service.password = this.password;
            },

            confirm_password() {
                this.service.confirm_password = this.confirm_password;
            },

            first_name() {
                this.service.first_name = this.first_name;
            },

            middle_name() {
                this.service.middle_name = this.middle_name;
            },

            last_name() {
                this.service.last_name = this.last_name;
            },

            email() {
                this.service.email = this.email;
            },

            role() {
                this.service.role = this.role;
            }
        }
    }
</script>