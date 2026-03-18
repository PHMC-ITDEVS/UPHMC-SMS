<template>
    <div class="phonebook-information">
        <div class="field d-flex justify-content-center mb-3">
            <ImageUpload
                :url="displayAvatar"
                @setImage="setImage"
                :containerWidth=200
                :containerHeight=150
            />
        </div>

        <v-field as="div" class="field" slim name="name" vid="name" v-slot="{ errors }">
            <label for="name">Name</label>
                <p-input-text
                    type="text"
                    v-model="data.name"
                    class="form-control shadow-none"
                    maxlength="50"
                    :class="{ 'p-invalid': errors[0] }"
                />
            <small class="p-error">{{ errors[0] }}</small>
        </v-field>

        <v-field as="div" class="field" slim name="phone_number" vid="phone_number" v-slot="{ errors }">
            <label for="phone_number">Phone Number</label>
            <p-input-group 
                :class="{ 'p-invalid': errors[0] || phoneNumberError }"
            >
                <p-input-group-addon>+63</p-input-group-addon>
                <p-input-number
                    v-model="data.phone_number" 
                    placeholder="9#########" 
                    maxlength="10"
                    class="form-control shadow-none p-0"
                    :useGrouping="false"
                    @input="formatPhoneNumber"
                />
            </p-input-group>
            <small class="p-error">{{ errors[0] || phoneNumberError }}</small>
        </v-field>

        <v-field as="div" class="field" slim name="notes" vid="notes" v-slot="{ errors }">
                <label for="notes">Notes</label>
                <p-textarea 
                    v-model="data.notes" 
                    rows="3" 
                    cols="30" 
                    style="resize: none" 
                    :class="{ 'p-invalid': errors[0] }"
                    class="form-control w-full"
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
                data:{
                    new_avatar: null,
                    image: null,
                    avatar: null,
                    name: null,
                    phone_number: null,
                    notes: null,
                },
                phoneNumberError: '',
            }
        },

        methods: {
            fillInfo() {
                Object.entries(this.data).forEach((item) => {
                    let label = item[0];
                    this.data[label] = (label.includes('date') && this.service.data[label]) ? this.formatDate(this.parseDate(this.service.data[label])) : this.service.data[label];
                    
                });
            },

            setImage(base64) {
                this.data.image = base64;
                this.data.new_avatar = base64;
            },

            // lets create a function for phone_number formatting and limitting the input to 10 digits
            formatPhoneNumber(event) {
                let phone_number = String(event?.value ?? '');
                if (!phone_number) {
                    this.data.phone_number = null;
                    this.phoneNumberError = '';
                    return;
                }
                let digits = phone_number.replace(/\D/g, '');

                if (digits.length > 10) this.phoneNumberError = 'Phone number cannot exceed 10 digits.';
                else this.phoneNumberError = '';

                this.data.phone_number = digits;
            }
        },

        computed: {
            displayAvatar() {
                return this.data.new_avatar || this.data.image || this.data.avatar || null;
            }
        },

        mounted() {
            this.fillInfo();
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
