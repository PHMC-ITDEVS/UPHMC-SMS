<template>
    <app-layout>
        <app-breadcrumb
            :title="page.title"
            :links="breadcrumbs"
        />

        <div class="profile-page">
            <section class="profile-hero card">
                <div class="profile-hero__pattern"></div>

                <div class="profile-hero__content">
                    <div class="profile-hero__identity">
                        <div class="profile-hero__avatar">
                            <img
                                v-if="form.image"
                                :src="form.image"
                                :alt="fullName"
                            >
                            <span v-else>{{ initials }}</span>
                        </div>

                        <div class="profile-hero__copy">
                            <span class="profile-badge">Account Settings</span>
                            <h1 class="text-capitalize">{{ fullName }}</h1>
                            <p>{{ _user?.email?.toLowerCase() }}</p>

                            <div class="profile-hero__meta">
                                <span>{{ (_user?.role_name || 'Staff').toUpperCase() }}</span>
                                <span>{{ _user?.department_name || 'No department assigned' }}</span>
                                <span>{{ _user?.position_name || 'No position assigned' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="profile-hero__stats">
                        <div class="profile-stat-card">
                            <small>Username</small>
                            <strong>{{ _user?.username || '-' }}</strong>
                        </div>
                        <div class="profile-stat-card">
                            <small>Last Login</small>
                            <strong>{{ _user?.last_login_at || 'No login yet' }}</strong>
                        </div>
                        <div class="profile-stat-card">
                            <small>Password Status</small>
                            <strong>{{ _auth?.must_change_password ? 'Needs Change' : 'Active' }}</strong>
                        </div>
                    </div>
                </div>
            </section>

            <div class="row profile-grid">
                <div class="col-xl-4">
                    <div class="profile-side-panel card">
                        <div class="profile-panel__header">
                            <span class="profile-panel__eyebrow">Identity</span>
                            <h3>Public-facing account details</h3>
                            <p>Update the information people inside the system see when they interact with your account.</p>
                        </div>

                        <div class="profile-image-panel">
                            <ImageUpload
                                :url="form.image"
                                :containerWidth="168"
                                :containerHeight="168"
                                @setImage="setImage"
                            />

                            <div class="profile-image-panel__note">
                                <strong>Profile photo</strong>
                                <span>Use a clean square image for better recognition across the dashboard.</span>
                            </div>
                        </div>

                        <div
                            v-if="_auth?.must_change_password"
                            class="profile-security-alert"
                        >
                            <span class="profile-security-alert__icon"><i class="pi pi-shield"></i></span>
                            <div>
                                <strong>Temporary password detected</strong>
                                <p class="mb-0">Change your password now before continuing to other parts of the system.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <div class="profile-form-card card">
                        <div class="profile-panel__header">
                            <span class="profile-panel__eyebrow">Profile</span>
                            <h3>Personal information</h3>
                            <p>Keep your account details current so messages, audit history, and activity records stay accurate.</p>
                        </div>

                        <div class="card-body pt-0">
                            <v-form ref="form" class="row g-4">
                                <alert-errors :form="form" message="There were some problems with your input." />

                                <div class="col-md-6">
                                    <v-field as="div" class="field profile-field" slim rules="required" name="first_name" v-slot="{ errors }">
                                        <label class="profile-label">First Name</label>
                                        <p-input-text
                                            v-model="form.first_name"
                                            class="form-control shadow-none text-uppercase profile-input"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <small class="p-error">{{ errors[0] }}</small>
                                    </v-field>
                                </div>

                                <div class="col-md-6">
                                    <v-field as="div" class="field profile-field" slim name="middle_name" v-slot="{ errors }">
                                        <label class="profile-label">Middle Name</label>
                                        <p-input-text
                                            v-model="form.middle_name"
                                            class="form-control shadow-none text-uppercase profile-input"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <small class="p-error">{{ errors[0] }}</small>
                                    </v-field>
                                </div>

                                <div class="col-md-6">
                                    <v-field as="div" class="field profile-field" slim rules="required" name="last_name" v-slot="{ errors }">
                                        <label class="profile-label">Last Name</label>
                                        <p-input-text
                                            v-model="form.last_name"
                                            class="form-control shadow-none text-uppercase profile-input"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <small class="p-error">{{ errors[0] }}</small>
                                    </v-field>
                                </div>

                                <div class="col-md-6">
                                    <v-field as="div" class="field profile-field" slim rules="required|email" name="email" v-slot="{ errors }">
                                        <label class="profile-label">Email Address</label>
                                        <p-input-text
                                            v-model="form.email"
                                            type="email"
                                            class="form-control shadow-none profile-input"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <small class="p-error">{{ errors[0] }}</small>
                                    </v-field>
                                </div>
                            </v-form>
                        </div>

                        <div class="profile-card__footer">
                            <p-button
                                class="btn btn-primary profile-action-btn"
                                :loading="loading"
                                label="Save Changes"
                                icon="pi pi-save"
                                @click="update"
                            />
                        </div>
                    </div>

                    <div class="profile-form-card card mt-4">
                        <div class="profile-panel__header">
                            <span class="profile-panel__eyebrow">Security</span>
                            <h3>Change password</h3>
                            <p>Rotate your password regularly and use a strong combination that is unique to this account.</p>
                        </div>

                        <div class="card-body pt-0">
                            <v-form ref="passwordForm" class="row g-4">
                                <alert-errors :form="password" message="There were some problems with your input." />

                                <div class="col-md-4">
                                    <v-field as="div" class="field profile-field" slim rules="required" name="current_password" v-slot="{ errors }">
                                        <label class="profile-label">Current Password</label>
                                        <p-input-password
                                            v-model="password.current_password"
                                            toggleMask
                                            :feedback="false"
                                            class="d-block"
                                            inputClass="form-control shadow-none profile-input"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <small class="p-error">{{ errors[0] }}</small>
                                    </v-field>
                                </div>

                                <div class="col-md-4">
                                    <v-field as="div" class="field profile-field" slim rules="required|min:8" name="password" v-slot="{ errors }">
                                        <label class="profile-label">New Password</label>
                                        <p-input-password
                                            v-model="password.password"
                                            toggleMask
                                            :feedback="false"
                                            class="d-block"
                                            inputClass="form-control shadow-none profile-input"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <small class="p-error">{{ errors[0] }}</small>
                                    </v-field>
                                </div>

                                <div class="col-md-4">
                                    <v-field as="div" class="field profile-field" slim rules="required" name="password_confirmation" v-slot="{ errors }">
                                        <label class="profile-label">Confirm Password</label>
                                        <p-input-password
                                            v-model="password.password_confirmation"
                                            toggleMask
                                            :feedback="false"
                                            class="d-block"
                                            inputClass="form-control shadow-none profile-input"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <small class="p-error">{{ errors[0] }}</small>
                                    </v-field>
                                </div>
                            </v-form>
                        </div>

                        <div class="profile-card__footer profile-card__footer--split">
                            <div class="profile-password-tip">
                                <i class="pi pi-lock"></i>
                                <span>Use at least 8 characters and avoid reusing old passwords.</span>
                            </div>

                            <p-button
                                class="btn btn-primary profile-action-btn"
                                :loading="passwordLoading"
                                label="Update Password"
                                icon="pi pi-shield"
                                @click="updatePassword"
                            />
                        </div>
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
            passwordLoading: false,
            form: new Form({
                first_name: '',
                middle_name: '',
                last_name: '',
                email: '',
                image: '',
                new_avatar: null,
            }),
            password: new Form({
                current_password: '',
                password: '',
                password_confirmation: '',
            }),
        }
    },

    computed: {
        fullName() {
            return [
                this.form.first_name,
                this.form.middle_name,
                this.form.last_name,
            ].filter(Boolean).join(' ') || 'Your Profile';
        },

        initials() {
            return this.fullName
                .split(' ')
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part.charAt(0).toUpperCase())
                .join('') || 'UP';
        },
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

        updatePassword() {
            if (this.passwordLoading) return;

            if (this.password.password !== this.password.password_confirmation) {
                this.$refs.passwordForm.setErrors({
                    password_confirmation: ['Password confirmation does not match.'],
                });
                return;
            }

            this.passwordLoading = true;

            AccountService.update_password(this.password)
                .then((response) => {
                    this.$toast.add({
                        severity: 'success',
                        summary: 'Success!',
                        detail: response.data.message,
                        life: 3000,
                    });
                    this.password.current_password = '';
                    this.password.password = '';
                    this.password.password_confirmation = '';

                    if (this._auth) {
                        this._auth.must_change_password = false;
                    }

                    this.router.visit('/');
                })
                .catch((errors) => {
                    try {
                        this.$refs.passwordForm.setErrors(this.getError(errors));
                    } catch (error) {
                        console.log(error);
                    }
                })
                .finally(() => {
                    this.passwordLoading = false;
                });
        },
    },
}
</script>

<style scoped>
.profile-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.profile-hero {
    position: relative;
    overflow: hidden;
    border: 0;
    border-radius: 28px;
    background:
        radial-gradient(circle at top right, rgba(255, 255, 255, 0.24), transparent 28%),
        linear-gradient(135deg, #0f766e 0%, #115e59 45%, #123c69 100%);
    box-shadow: 0 24px 60px rgba(18, 60, 105, 0.18);
}

.profile-hero__pattern {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(90deg, rgba(255,255,255,0.08) 1px, transparent 1px),
        linear-gradient(rgba(255,255,255,0.08) 1px, transparent 1px);
    background-size: 34px 34px;
    mask-image: linear-gradient(to bottom, rgba(0,0,0,0.9), rgba(0,0,0,0.2));
    opacity: 0.35;
}

.profile-hero__content {
    position: relative;
    display: flex;
    justify-content: space-between;
    gap: 1.5rem;
    padding: 2rem;
    color: #f4fbfa;
}

.profile-hero__identity {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    min-width: 0;
}

.profile-hero__avatar {
    width: 92px;
    height: 92px;
    border-radius: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.16);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.2);
    overflow: hidden;
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: 0.08em;
}

.profile-hero__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-hero__copy h1 {
    margin: 0.35rem 0 0.4rem;
    font-size: clamp(1.75rem, 3vw, 2.6rem);
    font-weight: 700;
    color: #ffffff;
}

.profile-hero__copy p {
    margin: 0;
    font-size: 1rem;
    color: rgba(244, 251, 250, 0.82);
}

.profile-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.38rem 0.8rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.15);
    font-size: 0.75rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.profile-hero__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    margin-top: 1rem;
}

.profile-hero__meta span {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 0.8rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    font-size: 0.82rem;
    color: #ffffff;
}

.profile-hero__stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(150px, 1fr));
    gap: 0.85rem;
    min-width: 420px;
}

.profile-stat-card {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 112px;
    padding: 1rem 1.1rem;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(14px);
}

.profile-stat-card small {
    color: rgba(244, 251, 250, 0.74);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.profile-stat-card strong {
    font-size: 1rem;
    font-weight: 600;
    color: #ffffff;
}

.profile-grid {
    row-gap: 1.5rem;
}

.profile-side-panel,
.profile-form-card {
    border: 0;
    border-radius: 24px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.profile-side-panel {
    height: 100%;
    background:
        radial-gradient(circle at top left, rgba(15, 118, 110, 0.08), transparent 35%),
        linear-gradient(180deg, #ffffff 0%, #f6faf9 100%);
}

.profile-panel__header {
    padding: 1.5rem 1.5rem 0;
}

.profile-panel__eyebrow {
    display: inline-block;
    margin-bottom: 0.65rem;
    font-size: 0.76rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #0f766e;
    font-weight: 700;
}

.profile-panel__header h3 {
    margin: 0 0 0.45rem;
    font-size: 1.45rem;
    font-weight: 700;
    color: #102a43;
}

.profile-panel__header p {
    margin: 0;
    color: #627d98;
    line-height: 1.65;
}

.profile-image-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
}

.profile-image-panel__note {
    padding: 1rem 1.1rem;
    width: 100%;
    border-radius: 18px;
    background: #eef8f7;
    border: 1px solid #d4ece9;
    text-align: center;
}

.profile-image-panel__note strong {
    display: block;
    color: #123c69;
    margin-bottom: 0.25rem;
}

.profile-image-panel__note span {
    color: #5c6f82;
    font-size: 0.92rem;
}

.profile-security-alert {
    display: flex;
    gap: 0.9rem;
    margin: 0 1.5rem 1.5rem;
    padding: 1rem 1.1rem;
    border-radius: 18px;
    background: linear-gradient(135deg, #fff4d9, #fff9eb);
    border: 1px solid #f6db8b;
    color: #6c4d00;
}

.profile-security-alert__icon {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: rgba(243, 171, 26, 0.16);
    flex-shrink: 0;
}

.profile-form-card .card-body {
    padding: 1.5rem;
}

.profile-field {
    margin-bottom: 0;
}

.profile-label {
    display: block;
    margin-bottom: 0.55rem;
    font-size: 0.86rem;
    font-weight: 600;
    color: #334e68;
}

.profile-input {
    border-radius: 16px;
    min-height: 52px;
    border: 1px solid #d9e2ec;
    background: #fbfdff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.profile-input:focus,
:deep(.profile-input:focus) {
    border-color: #0f766e;
    box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.12);
    background: #ffffff;
}

.profile-card__footer {
    display: flex;
    justify-content: flex-end;
    padding: 0 1.5rem 1.5rem;
}

.profile-card__footer--split {
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.profile-password-tip {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    color: #52667a;
}

.profile-password-tip i {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: #e7f5f3;
    color: #0f766e;
}

.profile-action-btn {
    min-width: 172px;
    border-radius: 14px;
    padding-inline: 1rem;
}

@media (max-width: 1199.98px) {
    .profile-hero__content {
        flex-direction: column;
    }

    .profile-hero__stats {
        min-width: 0;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .profile-hero {
        border-radius: 22px;
    }

    .profile-hero__content {
        padding: 1.25rem;
    }

    .profile-hero__identity {
        flex-direction: column;
        align-items: flex-start;
    }

    .profile-hero__stats {
        grid-template-columns: 1fr;
    }

    .profile-panel__header,
    .profile-form-card .card-body,
    .profile-card__footer {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .profile-card__footer--split {
        flex-direction: column;
        align-items: stretch;
    }

    .profile-action-btn {
        width: 100%;
    }
}
</style>
