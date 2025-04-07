<template>
  <guest-layout>
    <v-app-bar color="primary" :clipped-left="false" fixed dark app>
      <v-toolbar-title v-text="_app_name" />
      <v-spacer />
      <Link v-if="$page.props.auth.user" :href="route('dashboard')">
        <v-btn text>Dashboard</v-btn>
      </Link>
      <div v-else>
        <Link :href="route('login')">
          <v-btn text>Login</v-btn>
        </Link>
        <Link :href="route('register')">
          <v-btn text>Register</v-btn>
        </Link>
      </div>
    </v-app-bar>
    <v-main>
      <v-container>
        <v-card>
          <v-card-title></v-card-title>
          <v-card-text>
            <img src="images/tax2.png" width="100%"/>
          </v-card-text>
        </v-card>
      </v-container>
    </v-main>
  </guest-layout>
</template>

<script>

export default {
  computed: {
    appName() {
      return this.$page.props.appName
    },
  },
}
</script>
