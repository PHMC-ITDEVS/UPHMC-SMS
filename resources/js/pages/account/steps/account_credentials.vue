<template>
    <div class="d-flex flex-wrap flex-column">
        <div class="field">
            <label for="role">Role</label>
            <p-dropdown
                v-model="data.role_name"
                inputId="role"
                :options="roles"
                optionLabel="display_name"
                optionValue="name"
                :loading="rolesLoading"
                :virtualScrollerOptions="virtualScrollerOptions"
                @show="ensureRolesLoaded"
                inputClass="w-full"
            />
        </div>

        <v-field as="div" class="field" slim rules="required" name="email" v-slot="{ errors }">
            <label for="email">Email address</label>
            <p-input-text
                id="email"
                type="text"
                v-model="data.email"
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
                v-model="data.username"
                class="form-control shadow-none"
                maxlength="50"
                :class="{ 'p-invalid': errors[0] }"
            />
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>
        
        <v-field as="div" class="field" name="password" v-slot="{ errors }" v-if="!service.id">
            <label for="password">Password</label> 
            <p-input-password
                id="password"
                v-model="data.password"
                toggleMask
                :feedback=false
                :class="{ 'p-invalid': errors[0] }"
                maxlength="50"
                class="w-100"
                inputClass="form-control shadow-none"
            />
            
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" name="confirm_password" v-slot="{ errors }" v-if="data.password">
            <label for="confirm_password">Confirm Password</label>
            <p-input-password
                id="confirm_password"
                v-model="data.confirm_password"
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
    import RoleServices from '../../../services/role'

    export default {
        components : { ImageUpload },
        props: ['service'],

        data() {
            return {
                data:{
                    username: null,
                    password: null,
                    confirm_password: null,
                    email: null,
                    role_name: null,
                },
                roles: [],
                rolesLoading: false,
                rolesPage: 0,
                rolesTotalPages: 1,
                rolesRequestKey: 0,
            }
        },

        methods: {
            async loadRoles(page = 1) {
                if (this.rolesLoading || page > this.rolesTotalPages) return;

                this.rolesLoading = true;
                const requestKey = ++this.rolesRequestKey;

                try {
                    const response = await RoleServices.list({
                        page,
                        limit: 20,
                    });

                    if (requestKey !== this.rolesRequestKey) return;

                    const payload = response.data.data;
                    const rows = payload.data ?? [];

                    this.roles = page === 1
                        ? rows
                        : [
                            ...this.roles,
                            ...rows.filter((role) => !this.roles.some((item) => item.id === role.id))
                        ];

                    this.rolesPage = payload.current_page ?? page;
                    this.rolesTotalPages = payload.last_page ?? page;
                } catch (error) {
                    console.log(error);
                } finally {
                    this.rolesLoading = false;
                }
            },

            ensureRolesLoaded() {
                if (!this.roles.length) {
                    this.loadRoles(1);
                }
            },

            onRoleLazyLoad(event) {
                if (this.rolesLoading || this.rolesPage >= this.rolesTotalPages) return;

                const lastVisibleIndex = event.last ?? 0;

                if (lastVisibleIndex >= this.roles.length - 1) {
                    this.loadRoles(this.rolesPage + 1);
                }
            },

            fillInfo() {
                Object.entries(this.data).forEach((item) => {
                    let label = item[0];

                    this.data[label] = (label.includes('date') && this.service.data[label]) ? this.formatDate(this.parseDate(this.service.data[label])) : this.service.data[label];
                    
                });
            },

            setImage(base64) {
                this.image = base64;
            }
        },

        mounted() {
            this.loadRoles(1);
            this.fillInfo();
        },

        computed: {
            virtualScrollerOptions() {
                return {
                    lazy: true,
                    onLazyLoad: this.onRoleLazyLoad,
                    itemSize: 38,
                    showLoader: true,
                    loading: this.rolesLoading,
                    delay: 100,
                };
            }
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
