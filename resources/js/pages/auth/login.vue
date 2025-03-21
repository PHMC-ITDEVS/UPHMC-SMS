<template>
    <div>
        <app-guest>
            <div class="card shadow-none">
                <div class="card-body p-3">
                    <div class="text-center w-100 m-auto">
                        <div class="auth-logo">
                            <Link href="/" class="logo logo-dark text-center">
                                <span class="logo-lg">
                                <!-- <app-logo /> -->
                                <!-- <h1>Tax Engine</h1> -->
                                </span>
                            </Link>
                        </div>
                        <p class="text-muted mb-2 mt-2">Enter your username and password <br>to access admin panel.</p>
                    </div>

                    <v-form ref="form" as="form">
                        <alert-errors :form="form" message="There were some problems with your input."></alert-errors>
                        <v-field slim  class="form-group" as="div" rules="required" name="username" vid="username" v-slot="{ errors }">
                            <label class="tx-10 tx-uppercase tx-medium tx-spacing-1 mg-b-5 tx-color-03">Username</label>
                                <p-input-text
                                    type="text"
                                    v-model="form.username"
                                    placeholder="Enter your username"
                                    class="form-control shadow-none"
                                    :class="{ 'p-invalid': errors[0] }"
                                    @keyup.enter="login"
                                />
                                <small class="p-error">{{ errors[0] }}</small>
                        </v-field>
                        
                        <v-field slim class="form-group mt-2" as="div" name="password" vid="password" v-slot="{ errors }">
                            <label>Password</label>
                            <p-input-password
                                class="d-block"
                                v-model="form.password"
                                placeholder="Enter your password"
                                toggleMask
                                :feedback=false
                                @keyup.enter="login"
                                inputClass="form-control"
                            />
                        </v-field>

                        <div class="text-center d-grid mt-2">
                            <p-button
                            type="submit"
                            label="Log in"
                            :disabled="disabled_login"
                            class="btn btn-primary"
                            @click="login"
                            />
                        </div>
                    </v-form>
                </div> <!-- end card-body -->
            </div>
        </app-guest>
    </div>
</template>

<script>
import AuthService  from '../../services/auth';

export default {
  components: { },
  data() {
    return {
      valid: false,
      loading:false,
      form: new Form({
          username: "",
          password: "",
      })
    };
  },
  computed:{
    disabled_login() {
      // return false;
      if (this.loading) return true;
      return (this.form.username.length > 0 && this.form.password.length > 0) ? false : true;
    }
  },
  methods: {
    login() { 
      // this.form.reset();
      if (this.loading) return;
      this.loading = true;
      AuthService.login(this.form)
        .then((response) => {
          this.router.visit("/");
          document.body.classList.remove("authentication-bg");
        })
        .catch((errors) => {
            try { 
              // this.form.errors = this.getError(errors); 
              this.$refs.form.setErrors(this.getError(errors));
            }
            catch(ex){
              console.log(ex)
            }
            this.loading = false;
        })
    }
  },
  mounted() {
    document.body.classList.add("authentication-bg");
  }
};
</script>