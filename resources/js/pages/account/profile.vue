<template>
    <app-layout>
        <app-breadcrumb
            :title="page.title"
            :links="breadcrumbs"
        />

        <div class="row">
            <div class="col-md-7 col-lg-6">
                <div class="card">
                    <div class="card-header border-bottom">
                        <h3 class="card-title mb-0">Profile Information</h3>
                    </div>

                    <div class="card-body">
                        <v-form ref="form" class="row">
                            <alert-errors :form="form" message="There were some problems with your input." />

                            <div class="col-12 d-flex justify-content-center mb-4">
                                <ImageUpload
                                    :url="form.image"
                                    :containerWidth="150"
                                    :containerHeight="150"
                                    @setImage="setImage"
                                />
                            </div>

                            <div class="col-12">
                                <v-field as="div" class="field" slim rules="required" name="first_name" v-slot="{ errors }">
                                    <span class="p-float-label">
                                        <p-input-text
                                            v-model="form.first_name"
                                            class="form-control shadow-none text-uppercase"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <label>First Name</label>
                                    </span>
                                    <small class="p-error">{{ errors[0] }}</small>
                                </v-field>
                            </div>

                            <div class="col-12">
                                <v-field as="div" class="field" slim name="middle_name" v-slot="{ errors }">
                                    <span class="p-float-label">
                                        <p-input-text
                                            v-model="form.middle_name"
                                            class="form-control shadow-none text-uppercase"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <label>Middle Name</label>
                                    </span>
                                    <small class="p-error">{{ errors[0] }}</small>
                                </v-field>
                            </div>

                            <div class="col-12">
                                <v-field as="div" class="field" slim rules="required" name="last_name" v-slot="{ errors }">
                                    <span class="p-float-label">
                                        <p-input-text
                                            v-model="form.last_name"
                                            class="form-control shadow-none text-uppercase"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <label>Last Name</label>
                                    </span>
                                    <small class="p-error">{{ errors[0] }}</small>
                                </v-field>
                            </div>

                            <div class="col-12">
                                <v-field as="div" class="field" slim rules="required|email" name="email" v-slot="{ errors }">
                                    <span class="p-float-label">
                                        <p-input-text
                                            v-model="form.email"
                                            type="email"
                                            class="form-control shadow-none"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <label>Email Address</label>
                                    </span>
                                    <small class="p-error">{{ errors[0] }}</small>
                                </v-field>
                            </div>
                        </v-form>
                    </div>

                    <div class="card-footer text-right">
                        <p-button
                            class="btn btn-primary"
                            :loading="loading"
                            label="Save Changes"
                            icon="pi pi-save"
                            @click="update"
                        />
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
import AccountService from '../../services/account';
import ImageUpload from '../../components/image-upload';

export default {
    components: { ImageUpload },

    data() {
        return {
            page: {
                title: 'Profile',
                route: 'profile',
            },
            breadcrumbs: [
                { current: false, title: 'Home', url: 'dashboard' },
                { current: true, title: 'Profile', url: 'profile.index' },
            ],
            loading: false,
            form: new Form({
                first_name: '',
                middle_name: '',
                last_name: '',
                email: '',
                image: '',
                new_avatar: null,
            }),
        }
    },

    mounted() {
        this.form.first_name = this._account?.first_name ?? '';
        this.form.middle_name = this._account?.middle_name ?? '';
        this.form.last_name = this._account?.last_name ?? '';
        this.form.email = this._user?.email ?? '';
        this.form.image = this._account?.avatar ?? '';
    },

    methods: {
        setImage(base64) {
            this.form.image = base64;
            this.form.new_avatar = base64;
        },

        update() {
            if (this.loading) return;

            this.loading = true;

            AccountService.update_profile(this.form)
                .then((response) => {
                    this.swalMessage('success', response.data.message, 'Okay', false, false, false);

                    this._user.email = this.form.email;
                    this._account.first_name = this.form.first_name;
                    this._account.middle_name = this.form.middle_name;
                    this._account.last_name = this.form.last_name;
                    if (this.form.new_avatar) {
                        this._account.avatar = this.form.image;
                    }
                    this.form.new_avatar = null;
                })
                .catch((errors) => {
                    try {
                        this.$refs.form.setErrors(this.getError(errors));
                    } catch (error) {
                        console.log(error);
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
        },
    },
}
</script>
