<template>
    <app-layout>
      <app-breadcrumb
        :title="page.title"
        :links="breadcrumbs"
      ></app-breadcrumb>
  
      <div class="row">
        <div class="col-md-8 col-12 mx-auto">
            <div class="card h-100 p-3">
                <div class="card-body">
                    <v-form ref="form" class="row">
                        <alert-errors :form="form" message="There were some problems with your input." />
                        <div class="col-12 col-md-4">
                            <div class="field d-flex justify-content-center mb-3">
                                <ImageUpload
                                    :url="form.image"
                                    @setImage="setImage"
                                    :containerWidth=150
                                    :containerHeight=150
                                />
                            </div>
                        </div>
                        <div class="col-12 col-md-8">
                            <div class="row">

                                <v-field as="div" class="field" slim rules="" name="first_name" vid="first_name" v-slot="{ errors }">
                                    <span class="p-float-label">
                                        <p-input-text
                                            type="text"
                                            v-model="form.first_name"
                                            class="form-control shadow-none text-uppercase"
                                            maxlength="50"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <label>First Name</label>
                                    </span>
                                    <small class="p-error">{{ errors[0] }}</small>
                                </v-field>

                                <v-field as="div" class="field" slim rules="" name="middle_name" vid="middle_name" v-slot="{ errors }">
                                    <span class="p-float-label">
                                        <p-input-text
                                            type="text"
                                            v-model="form.middle_name"
                                            class="form-control shadow-none text-uppercase"
                                            maxlength="50"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <label>Middle Name</label>
                                    </span>
                                    <small class="p-error">{{ errors[0] }}</small>
                                </v-field>

                                <v-field as="div" class="field" slim rules="" name="last_name" vid="last_name" v-slot="{ errors }">
                                    <span class="p-float-label">
                                        <p-input-text
                                            type="text"
                                            v-model="form.last_name"
                                            class="form-control shadow-none text-uppercase"
                                            maxlength="50"
                                            :class="{ 'p-invalid': errors[0] }"
                                        />
                                        <label>Last Name</label>
                                    </span>
                                    <small class="p-error">{{ errors[0] }}</small>
                                </v-field>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <v-field as="div" class="field" slim rules="required" name="email" vid="email" v-slot="{ errors }">
                                <span class="p-float-label">
                                    <p-input-text
                                        type="email"
                                        v-model="form.email"
                                        class="form-control shadow-none text-uppercase"
                                        maxlength="50"
                                        :class="{ 'p-invalid': errors[0] }"
                                    />
                                    <label>Email address</label>
                                </span>
                                <small class="p-error">{{ errors[0] }}</small>
                            </v-field>
                        </div>

                        <div class="col-12 col-md-6">
                            <v-field as="div" class="field" slim rules="required" name="username" vid="username" v-slot="{ errors }">
                                <span class="p-float-label">
                                    <p-input-text
                                        type="text"
                                        v-model="form.username"
                                        class="form-control shadow-none text-uppercase"
                                        maxlength="50"
                                        :class="{ 'p-invalid': errors[0] }"
                                    />
                                    <label>Username</label>
                                </span>
                                <small class="p-error">{{ errors[0] }}</small>
                            </v-field>
                        </div>

                        <div class="col-12 col-md-6">
                            <v-field as="div" class="field" slim name="password" vid="password" v-slot="{ errors }">
                                <span class="p-float-label">
                                    <p-input-password
                                        v-model="form.password"
                                        toggleMask
                                        :feedback=false
                                        maxlength="50"
                                        :inputClass="{
                                            'form-control shadow-none': !0,
                                            'p-invalid': errors[0]
                                        }"
                                    />
                                    <label>Change Password</label>
                                </span>
                                <small class="p-error">{{ errors[0] }}</small>
                            </v-field>
                        </div>

                        <div class="col-12 col-md-6">
                            <v-field as="div" class="field" slim name="confirm_password" vid="confirm_password" v-slot="{ errors }">
                                <span class="p-float-label">
                                    <p-input-password
                                        v-model="form.confirm_password"
                                        toggleMask
                                        :feedback=false
                                        maxlength="50"
                                        :inputClass="{
                                            'form-control shadow-none': !0,
                                            'p-invalid': errors[0]
                                        }"
                                    />
                                    <label>Confirm Password</label>
                                </span>
                                <small class="p-error">{{ errors[0] }}</small>
                            </v-field>
                        </div>
                    </v-form>
                </div> 

                <div class="card-footer text-right">
                    <p-button
                        class="btn btn-primary"
                        label="Apply Changes"
                        icon="fe-save"
                        @click="update()"
                    />
                </div>
                
            </div>
        </div>

      </div>
    </app-layout>
  </template>
  
  <script>
  import ImageUpload from '../../components/image-upload';
  import AccountService  from '../../services/account';
  
  export default {
    components: { ImageUpload },
    computed: {
      service_providers(){
        return this.getDataPerCompany();
      }
    },
    data() {
      return {
        page: {
          title:"Profile",
          route:"profile",
          interval:null
        },
        breadcrumbs: [
          { current: false, title: 'Home', url: 'dashboard' }
        ],
        form: new Form({
            email:"",
            first_name:"",
            last_name:"",
            username:"",
            password:"",
            confirm_password:"",
            image:""
        }),
        loading:false,
        dialog: {
          title: "",
          show:false,
          processing:false,
          type:1,
          logo:"",
          data:[]
        },
        generate_loading:false
      }
    },
    
    methods:{
        setImage(base64) {
            this.form.image = base64;
        },
        update(){
            if (this.loading) return;
            this.loading = true;
            AccountService.update_profile(this.form)
            .then((response) => {
                this.swalMessage("success",response.data.message,"Okay",false,false,false);
                this._user.username = this.form.username;
                this._user.email = this.form.email;
                this._account.first_name = this.form.first_name;
                this._account.middle_name = this.form.middle_name;
                this._account.last_name = this.form.last_name;
                this._account.avatar = this.form.image;
            })
            .catch((errors) => {
                try { 
                    this.$refs.form.setErrors(this.getError(errors));
                }
                catch(ex){ console.log(ex)}
            })
            .finally(() => {
                this.loading = false;
            });
        }
    },
  
    mounted(){
        this.form.username = this._user.username;
        this.form.email = this._user.email;
        this.form.first_name = this._account.first_name;
        this.form.middle_name = this._account.middle_name;
        this.form.last_name = this._account.last_name;
        this.form.image = this._account.avatar;
    },
  
    beforeDestroy() {
    },
  
    beforeUnmount() {
      clearInterval(this.page.interval);
    },

    watch: {
     
    }
  };
</script>