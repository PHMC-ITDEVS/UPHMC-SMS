<template>
    <div class="d-flex flex-wrap flex-column">
        <div class="field">
            <label for="role">Role</label>
            <p-dropdown
                v-model="role"
                inputId="role"
                :options='["ADMIN"]'
                inputClass="w-full"
            />
        </div>

        <v-field as="div" class="field" slim rules="required" name="email" v-slot="{ errors }">
            <label for="email">Email address</label>
            <p-input-text
                id="email"
                type="text"
                v-model="email"
                class="form-control shadow-none"
                maxlength="50"
                :class="{ 'p-invalid': errors[0] }"
            />
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" slim rules="required" name="username" v-slot="{ errors }">
            <label for="username">Username</label>
            <p-input-text
                id="username"
                type="text"
                v-model="username"
                class="form-control shadow-none"
                maxlength="50"
                :class="{ 'p-invalid': errors[0] }"
            />
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>
        
        <v-field as="div" class="field" name="password" v-slot="{ errors }">
            <label for="password">Password</label> 
            <p-input-password
                id="password"
                v-model="password"
                toggleMask
                :feedback=false
                :class="{ 'p-invalid': errors[0] }"
                maxlength="50"
                class="w-100"
                inputClass="form-control shadow-none"
            />
            
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" name="confirm_password" v-slot="{ errors }" v-if="password">
            <label for="confirm_password">Confirm Password</label>
            <p-input-password
                id="confirm_password"
                v-model="confirm_password"
                toggleMask
                :feedback=false
                maxlength="50"
                :class="{ 'p-invalid': errors[0] }"
                class="w-100"
                inputClass="form-control shadow-none"
            />
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
                role: 'DSP',
                region:[],
                region_list:[],
                selected_regions:[]
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
                this.region = this.service.region;
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

            region(){
                this.service.region = this.region;
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