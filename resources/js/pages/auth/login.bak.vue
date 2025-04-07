<template>
    <guest-layout>
      <v-main class="bg-image">
        <v-container fluid>
          <!-- <v-row align="center" justify="center" style="height: 100vh"> -->
            <v-row  style="height: 100vh;width: 100%;">
              <v-col class="pa-0"  style="max-width:500px;">
                <div class=" d-flex align-center h-100">
                  <v-card class="w-100">
                    <v-card-title class="d-flex align-center justify-center">
                      <Link href="/">
                        <application-logo width="200px" />
                      </Link>
                    </v-card-title>
                    <v-card-text class="pb-0">
                      <p class="font-weight-semibold text--primary mb-0">
                        Welcome to {{$page.props.appName}}! 👋🏻
                      </p>
                      <p class="mb-0">
                        Please sign-in to your account and start the adventure
                      </p>
                    </v-card-text>
                    <v-card-text>
                      <v-form @submit.prevent="login" ref="form" lazy-validation v-model="valid">
                        <v-text-field
                          class="unset-text-transform"
                          v-model="form.username"
                          prepend-inner-icon="mdi-account-key-outline"
                          label="Username"
                          type="text"
                          outlined
                          dense
                          :error-messages="form.errors.username"
                        />
                        <v-text-field
                          v-model="form.password"
                          prepend-inner-icon="mdi-lock"
                          label="Password"
                          outlined
                          dense
                          :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                          :type="showPassword ? 'text' : 'password'"
                          :error-messages="form.errors.password"
                          @click:append="showPassword = !showPassword"
                        />
                        <!-- <div class="d-flex align-center justify-space-between flex-wrap" >
                          <v-checkbox
                            v-model="form.remember_me"
                            label="Remember me"
                            hide-details="auto"
                          />
                          <Link :href="route('password.request')">
                            Forgot Password?
                          </Link>
                        </div> -->
                        <v-btn :loading="form.processing" type="submit" block color="primary" class="mt-0"
                          >Login</v-btn
                        >
                      </v-form>
                    </v-card-text>
                    <!-- <v-card-text
                      class="d-flex align-center justify-center flex-wrap mt-2"
                    >
                      <span class="me-2"> New on our platform? </span>
                      <Link :href="route('register')"> Create an account </Link>
                    </v-card-text> -->
                  </v-card>
                </div>
               
              </v-col>
              <v-col  class="pa-0">
  
              </v-col>
          </v-row>
        </v-container>
      </v-main>
    </guest-layout>
  </template>
  
  <script>
  
  export default {
    data() {
      return {
        valid:false,
        showPassword: false,
        isLoading: false,
        form: this.$inertia.form({
          username: null,
          password: null,
          remember_me: false,
        }),
      };
    },
    methods: {
      login() {
        // this.form.post("/login");
        let self = this;
  
        self.form.post(route('login'), {
            onFinish: () =>{
                self.form.reset('password');
                self.$refs.form.resetValidation();
            },
        });
      },
    },
  };
  </script>
  
  <style scoped>
    .v-card{
      background-color: #ffffffdb;
      padding:20px;
    }
  </style>