<template>
    <app-guest>
        <div class="login-logo"> 
            <a href="/">
                <img src="/images/phmc-banner.png" class="header-brand-img dark-logo login" alt="logo">
            </a> 
        </div>

        <div class="card mb-0">
            <div class="card-body">
                <div class="text-center mb-6">
                    <h2 class="mb-2">Login</h2>
                </div>

                <v-form ref="form" as="form">
                    <p-message class="alert alert-error py-1 mb-2" severity="error" v-text="error_message" v-if="error_message"/>
                    <v-field slim  class="form-group" as="div" rules="required" name="username" vid="username" v-slot="{ errors }">
                        <label class="form-label">Username</label>
                        <p-input-text
                            type="text"
                            v-model="form.username"
                            placeholder="Enter your username"
                            class="form-control"
                            :class="{ 'p-invalid': errors[0] }"
                            @keyup.enter="login"
                            @click.prevent="removeErrorMesasge"
                        />
                        <small class="p-error">{{ errors[0] }}</small>
                    </v-field>
                    
                    <v-field slim class="form-group" as="div" name="password" vid="password" v-slot="{ errors }">
                        <label class="form-label">Password</label>
                        <p-input-password
                            class="d-block"
                            v-model="form.password"
                            placeholder="Enter your password"
                            toggleMask
                            :feedback=false
                            @keyup.enter="login"
                            inputClass="form-control"
                            @click.prevent="removeErrorMesasge"
                        />
                    </v-field>

                    <div class="d-flex flex-row align-items-center justify-content-between">
                        <v-field slim class="form-group d-flex flex-row align-items-center gap-1 mb-0" as="div" name="remember">
                            <p-checkbox 
                                    v-model="form.remember" 
                                    class=""
                                    name="remember" 
                                    :binary="true"
                                    input-id="remember"
                                />
                            <label class="text-muted mb-0">Remember me</label> 
                        </v-field>

                        <!-- <div class="form-group mb-0"> 
                            <a href="/forgot-password" class="text-muted">Forgot password?</a> 
                        </div> -->
                    </div>

                    <div class="mt-5"> 
                        <p-button 
                            class="btn btn-lg btn-primary btn-block"
                            label="Login"
                            :disabled="disabled_login"
                            @keyup.enter="login"
                            @click.prevent="login()"
                        />
                    </div>

                    <!-- <div class="text-center mt-7 mb-5">
                        <div class="font-weight-normal fs-16 text-muted">You Don't have an account <a class="btn-link font-weight-normal" href="/register">Register Here</a></div>
                    </div> -->

                </v-form>
            </div>
        </div>
    </app-guest>
</template>

<script>
    import AuthService  from '../../services/auth';

    export default {
        data() {
            return {
                valid: false,
                loading:false,
                error_message:null,
                form: new Form({
                    username: null,
                    password: null,
                    remember: false
                })
            };
        },

        mounted() {
            this.fillInfo();
        },

        methods: {
            login() { 
                if (this.loading) return;
                
                this.loading = true;
                AuthService.login(this.form)
                .then((response) => {
                    const redirectTo = response?.data?.data?.redirect_to || "/";
                    this.router.visit(redirectTo);
                })
                .catch((errors) => {
                    console.log(errors);
                    
                    try { 
                        this.error_message = errors.response.data.message;
                    }
                    catch(ex) {
                        console.log(ex)
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
            },

            fillInfo() {
                if(this._cookie_username && this._cookie_password) {
                    this.form.username = this._cookie_username
                    this.form.password = this._cookie_password;
                    this.form.remember = true;
                }
            },

            removeErrorMesasge() {
                this.error_message = null;
            }
        },

        computed:{
            disabled_login() {
                if (this.loading) return true;
                return !(this.form.username && this.form.username.length > 0 && this.form.password && this.form.password.length > 0);
            }
        }
    };
</script>
<style scoped>
    .login-logo {
        margin: 0 auto;
        text-align: center;
        margin-bottom: 1.5rem;
    }
</style>
