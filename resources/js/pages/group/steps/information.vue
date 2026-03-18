<template>
    <div class="contaact-group-information">

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

        <v-field as="div" class="field" slim name="description" vid="description" v-slot="{ errors }">
                <label for="description">Description</label>
                <p-textarea 
                    v-model="data.description" 
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

    export default {
        props: ['service'],

        data() {
            return {
                data:{
                    name: null,
                    description: null,
                },
            }
        },

        methods: {
            fillInfo() {
                Object.entries(this.data).forEach((item) => {
                    let label = item[0];
                    this.data[label] = (label.includes('date') && this.service.data[label]) ? this.formatDate(this.parseDate(this.service.data[label])) : this.service.data[label];
                    
                });
            }
        },

        computed: {
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
