<template>
    <div>
        <div class="col-12">
            <h3 class="mb-3">Provider</h3>
        </div>

        <div class="col-12 mb-2">
            <p-button
                class="btn btn-soft-primary"
                label="Add"
                icon="pi pi-plus"
                @click="create"
            />
        </div>

        <div class="table-responsive field">
            <v-field as="div" class="field" slim name="service_provider" vid="service_provider" v-slot="{ errors }">
                <p-table
                    :rowHover="true"
                    :loading="table.loading"
                    :value="table.data"
                    showGridlines
                    responsiveLayout="scroll"
                    :tableClass="{
                        'table': !0,
                        'table-error':errors[0]
                    }"

                    :paginator="true"
                    :rows="10"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                >
                    <template #empty>No records found.</template>
                    <template #loading>Loading data. Please wait.</template>

                    <p-column field="id" header="No."></p-column>
                    <p-column header="Provider">
                        <template #body="{data}">
                            <div>
                                <img :src="data.logo" class="provider-logo" />
                                <span>{{ data.data.company_name }}</span>
                            </div>
                        </template>
                    </p-column>
                    <p-column>
                        <template #body="{data}">
                            <p-button
                                class="btn btn-sm btn-danger"
                                icon="pi pi-times"
                                @click="removeData(data)"
                            />
                        </template>
                    </p-column>
                </p-table>
                <small class="p-error">{{ errors[0] }}</small>
            </v-field>
        </div>

        <p-dialog
            class="provider-modal modal"
            v-model:visible="dialog.show"
            :style="{
                width: '100%',
                'max-width': '650px'
            }"
            modal
            >
            <template #header>
                <h3 v-text="dialog.title"></h3>
            </template>
            
            <div class="table-responsive">
                <p-table
                    tableClass="table"
                    :rowHover="true"
                    :loading="dialog.table.loading"
                    :value="dialog.table.data"
                    showGridlines
                    responsiveLayout="scroll"

                    :paginator="true"
                    :rows="10"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                >
                    <template #empty>No records found.</template>
                    <template #loading>Loading data. Please wait.</template>

                    <p-column field="id" header="No."></p-column>
                    <p-column header="Company">
                        <template #body="{data}">
                            <div>
                                <img :src="data.logo" class="provider-logo" />
                                <span>{{ data.company_name }}</span>
                            </div>
                        </template>
                    </p-column>
                    <p-column field="created_at" header="Date created"></p-column>
                    <p-column>
                        <template #body="{data}">
                            <p-button
                                class="btn btn-sm btn-info"
                                icon="pi pi-plus"
                                @click="selectData(data)"
                            />
                        </template>
                    </p-column>
                </p-table>
            </div>
            
            <template #footer></template>
        </p-dialog>
    </div>
</template>
<script>
    import ImageUpload from '../../../components/image-upload';

    export default {
        components : { ImageUpload },
        props: ['service'],

        data() {
            return {
                table : {
                    loading: false,
                    data: [],
                },
                dialog: {
                    title: "Service Providers",
                    show: false,

                    table: {
                        data: [],
                        loading: !1
                    }
                }
            }
        },

        methods: {
            create() {
                this.dialog.show = true;
                this.dialog.table.loading = !0;

                axios.get(route("service-provider.list"), {}).then(e=>{
                    this.dialog.table.data = e.data.data.data;
                    this.dialog.table.loading = !1;
                });
            },

            updatePropData() {
                this.service.service_provider = this.table.data;
            },

            selectData(item) {
                if(this.table.data.some(e=> e.service_provider_id==item.id))
                    this.$toast.error("Service Provider already exist");
                else
                    this.table.data.push({account_id:null, service_provider_id:item.id,data:item});

                // this.table.data.push(data);
                this.updatePropData();
                this.dialog.show = !1;
            },

            removeData(data) {
                let index = this.table.data.findIndex(e => e.id == data.id);

                this.$confirm.require({
                    message: 'Do you want to remove this provider?',
                    header: 'Delete Confirmation',
                    icon: 'pi pi-info-circle',
                    acceptClass: 'p-button-danger btn btn-danger',
                    accept: () => {
                        this.table.data.splice(index, 1);
                        // this.$toast.add({ severity: 'info', summary: 'Confirmed', detail: 'Record deleted', life: 3000 });
                    },
                    reject: () => {
                        
                    }
                });
            }
        },

        mounted() {
            this.table.data = this.service.service_provider;
        },

        watch: {
            
        }
    }
</script>
<style scoped>
    .provider-logo {
        max-height: 50px;
        /* max-width: 50px; */
        border-radius: 50%;
        margin-right: 0.325rem;
    }
</style>