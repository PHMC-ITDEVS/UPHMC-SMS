<template>
    <div>
        <app-layout>
            <app-breadcrumb
                :title="page.title"
                :links="breadcrumbs"
            ></app-breadcrumb>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="search-set">
                            <div class="search-field w-30">
                                <p-input-text
                                    v-model="table.params.search"
                                    class="form-control shadow-none"
                                    placeholder="Search"
                                />
                                <i class="search-icon pi pi-search"/>
                            </div>
                            <div class="button-container">
                                <p-button
                                    class="btn btn-primary"
                                    label="Send SMS"
                                    icon="pi pi-plus"
                                    @click="create"
                                />
                            </div>
                        </div>

                        <div class="table-responsive mb-3 p-3">
                            <p-table
                                :rowHover="true"
                                :loading="table.loading"
                                :value="table.data"
                                responsiveLayout="scroll"
                            >
                                <template #empty>No records found.</template>
                                <template #loading><app-loader/></template>
                                <p-column header="Sender">
                                    <template #body="{data}">
                                        <span class="text-capitalize">{{ data.sender?.name }}</span>
                                    </template>
                                </p-column>
                                <p-column header="Message">
                                    <template #body="{data}">
                                        <div class="sms-message-clamp" :title="data.message_body">
                                            {{ data.message_body }}
                                        </div>
                                    </template>
                                </p-column>
                                <p-column header="Recipient">
                                    <template #body="{data}">
                                        <p-avatar-group :style="{'max-width': '200px'}">
                                            <p-avatar
                                                v-for="(recipient, index) in visibleRecipients(data.recipients)"
                                                :key="index"
                                                :image="recipient?.contact?.avatar || '/images/default-user.jpg'"

                                                shape="circle"
                                                size="small"
                                                v-tooltip.bottom="recipientTooltip(recipient)"
                                            />
                                            <p-avatar
                                                v-if="extraRecipientCount(data.recipients) > 0"
                                                :label="`+${extraRecipientCount(data.recipients)}`"
                                                shape="circle"
                                                size="small"
                                                v-tooltip.bottom="hiddenRecipientListTooltip(data.recipients)"
                                            />
                                        </p-avatar-group>
                                    </template>
                                </p-column>
                                <p-column header="Type">
                                    <template #body="{data}">
                                        <span class="text-capitalize">{{ data.type }}</span>
                                    </template>
                                </p-column>
                                <p-column header="status">
                                    <template #body="{data}">
                                        <span class="text-capitalize" :class="sms_status(data.status)">
                                            {{ data.status }}
                                        </span>
                                    </template>
                                </p-column>
                                
                                <p-column field="created_at" header="Date Created"></p-column>
                                <p-column header="Actions" style="width: 150px">
                                    <template #body="{data}">
                                        <div class="d-flex flex-row gap-2 actions-menu">
                                            <p-button
                                                v-if="data.type == 'immediate' || (data.type == 'scheduled' && data.status != 'draft')"
                                                icon="pi pi-eye"
                                                class="btn warning btn-sm"
                                                @click="viewData(data, 'view')"
                                            />
                                            
                                            <p-button
                                                v-else-if="data.type == 'scheduled' && data.status == 'draft'"
                                                icon="pi pi-pencil"
                                                class="btn info btn-sm"
                                                @click="viewData(data, 'edit')"
                                            />
                                            
                                            <p-button
                                                icon="pi pi-trash"
                                                class="btn danger btn-sm"
                                                @click="deleteData(data)"
                                            />
                                        </div>
                                    </template>

                                </p-column>
                            </p-table>
                        </div>

                        <p-paginator
                            v-if="table.total > 0"
                            v-model:first="table.first"
                            @page="onPaginate"
                            
                            :totalRecords="table.total"
                            :rows="table.params.limit"
                        >
                            <template #start>
                                <h5 class="mb-0">Showing {{ table.from }} to {{ table.to }} out of {{ table.total }} results</h5>
                            </template>
                        </p-paginator>
                    </div> 
                </div> <!-- end col -->
            </div>
        </app-layout>

        <p-dialog
            modal
            class="sms-modal"
            v-model:visible="dialog.show"
            :style="{
                'width': '100%',
                'max-width': '500px'
            }"
        >
            <template #header>
                <h3 v-text="dialog.title"></h3>
            </template>
            
            <SmsForm @hideModal="successEvent" :item="dialog.form" @close="dialog.show = !1" v-if="!dialog.loading"/>

            <div class="skeleton-cont" v-else>
                <p-skeleton class="mt-5 mb-4" height="1.3rem" borderRadius="16px"></p-skeleton>
                <p-skeleton height="1.5rem" width="10rem" class="mb-4" borderRadius="16px"></p-skeleton>

                <p-skeleton width="5rem" class="mb-2" borderRadius="16px"></p-skeleton>
                <p-skeleton height="2.25rem" class="mb-4"></p-skeleton>

                <p-skeleton width="5rem" class="mb-2" borderRadius="16px"></p-skeleton>
                <p-skeleton height="2.25rem" class="mb-4"></p-skeleton>

                <div class="d-flex flex-row justify-content-between mt-6">
                    <p-skeleton width="5rem" height="2.25rem"></p-skeleton>
                    <p-skeleton width="5rem" height="2.25rem"></p-skeleton>
                </div>
            </div>
        </p-dialog>
    </div>
</template>
  
<script>
    import SmsForm from './form';
    import SmsService  from '../../services/sms';

    export default {
        components: { SmsForm },
        data() {
            return {
                breadcrumbs: [
                    { current: false, title: 'Home', url: 'dashboard' }
                ],

                page: {
                    title: "SMS",
                    route: "sms.index",
                    interval: null
                },

                table: {
                    loading: !1,
                    data: [],
                    first: null,
                    total: 0,
                    total_pages: 0,
                    current_page:1,
                    params: {
                        search: "",
                        page: 1,
                        limit : 10
                    },
                },

                dialog: {
                    title: "",
                    show:!1,
                    loading: !1,
                    form_template: {
                        id: null,
                        page_type: 'create',
                        body: '',
                        recipients: [],
                        scheduled: false,
                        send_type: 'immediate',
                        scheduled_at: null,
                    },
                    form: new Form({}),
                }
            }
        },

        computed: {
        },
    
        mounted(){
            this.breadcrumbs.push({
                current: true,
                title: this.page.title,
                url: `${this.page.route}`
            });

            this.getTableData(1);
            this.socketListener();
        },

        methods: {
            socketListener() {
                this.emitter.off("sms_status");
                this.emitter.on("sms_status", data => {
                    this.getTableData(this.table.current_page , false);
                });
            },

            visibleRecipients(recipients = []) {
                return recipients.slice(0, 5);
            },

            extraRecipientCount(recipients = []) {
                return Math.max(recipients.length - 5, 0);
            },

            recipientTooltip(recipient) {
                if (recipient?.contact?.name && recipient?.contact?.phone_number) {
                    return `${recipient.contact.name} (${recipient.contact.phone_number})`;
                }

                return recipient?.phone_number ?? 'Unknown recipient';
            },

            recipientListTooltip(recipients = []) {
                return recipients
                    .map((recipient) => this.recipientTooltip(recipient))
                    .join('\n');
            },

            hiddenRecipientListTooltip(recipients = []) {
                return recipients
                    .slice(5)
                    .map((recipient) => this.recipientTooltip(recipient))
                    .join('\n');
            },

            onPaginate(e) {
                if(this.table.loading) return;
                this.table.current_page = e.page + 1;
                this.getTableData(this.table.current_page);
            },
            
            getTableData(page , without_loading = true) {
                if (this.table.loading) return;
                this.table.loading = without_loading;
                this.table.params.page = page;

                SmsService.list(this.table.params)
                .then((response) => {
                    let data = response.data.data;
                    this.table.data = data.data;
                    this.table.total = data.total;
                    this.table.total_pages = data.last_page;
                    this.table.from = data.from;
                    this.table.to = data.to;

                    if (page==1) this.table.first = 0;
                })
                .catch((errors) => {
                    try { 
                        this.getError(errors);
                    }
                    catch(ex){ console.log(ex)}
                })
                .finally(() => {
                    this.table.loading = false;
                });
            },

            create() {
                this.dialog.form = JSON.parse(JSON.stringify(this.dialog.form_template));;
                this.dialog.loading = !1;
                this.dialog.title = "Create";
                this.dialog.show = !0;
            },

            deleteData(item){
                this.$confirm.require({
                    message: 'Are you sure to delete this record?',
                    header: 'Delete Confirmation',
                    icon: 'pi pi-info-circle',
                    acceptClass: 'p-button-danger btn btn-light',
                    rejectClass: 'p-button-danger btn btn-danger',
                    accept: () => {
                    SmsService.remove(item.id)
                    .then((response) => {
                        this.$toast.add({ severity: 'success', summary: 'Success!', detail: response.data.message, life: 3000 });
                        this.getTableData(this.table.current_page);
                    })
                    .catch((errors) => {
                        try { 
                            this.getError(errors);
                        }
                        catch(ex){ console.log(ex)}
                    })
                    .finally(() => {});
                    }
                });
            },

            viewData(item, type){
                this.dialog.title = type === 'edit' ? `Modify Record` : `View Record`;
                this.dialog.form = JSON.parse(JSON.stringify(this.dialog.form_template));
                this.dialog.loading = !0;
                this.dialog.show = !0;

                SmsService.get(item.id)
                .then((response) => {
                    const data = response.data?.data ?? response.data;
                    const params = {
                        id: data.id,
                        page_type: type,
                        body: data.message_body ?? '',
                        recipients: (data.recipients || []).map((recipient) => ({
                            type: recipient.contact_id ? 'contact' : 'number',
                            value: recipient.contact_id ?? recipient.phone_number,
                            label: recipient.contact?.name
                                ? `${recipient.contact.name} (${recipient.phone_number})`
                                : recipient.phone_number,
                            name: recipient.contact?.name ?? null,
                            phone_number: recipient.phone_number,
                        })),
                        scheduled: data.type === 'scheduled',
                        send_type: data.type ?? 'immediate',
                        scheduled_at: data.scheduled_at,
                    };

                    this.dialog.form = new Form(params);
                    this.dialog.show = true;
                    this.dialog.loading = false;
                })
                .catch((errors) => {
                    console.log(errors);
                    try { 
                        // this.getError(errors);
                    }
                    catch(ex){ console.log(ex)}
                })
                .finally(() => {});
            },

            successEvent() {
                this.dialog.show = !1;
                this.getTableData(1);
            }
        },

        watch: {
            "table.params.search": function (val) {
                this.getTableData(1);
            }
        },

        beforeUnmount() {
            this.emitter.off("sms_status");
        }
    };
</script>

<style scoped>
.sms-message-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    white-space: pre-wrap;
    word-break: break-word;
    line-height: 1.45;
    max-width: 320px;
}
</style>
