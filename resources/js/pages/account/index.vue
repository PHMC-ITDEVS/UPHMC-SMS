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
                                <i class="search-icon pi pi-search" />
                            </div>
                            <div class="button-container">
                                <p-button
                                    class="btn btn-primary"
                                    label="Add Account"
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
                                <template #loading>Loading data. Please wait.</template>
                                <p-column>
                                    <template #body="{data}">
                                        <p-button
                                        class="btn btn-sm btn-warning"
                                        icon="pi pi-pencil"
                                        @click="viewData(data)"
                                        />
                                        <p-button
                                        class="btn btn-sm btn-danger"
                                        icon="pi pi-times"
                                        @click="deleteData(data)"
                                        />
                                    </template>
                                </p-column>
                                <p-column field="id" header="No."></p-column>
                                <p-column header="Name">
                                    <template #body="{data}">
                                        {{ `${ data.first_name ? data.first_name : "" } ${ data.last_name ? data.last_name : "" }` }}
                                    </template>
                                </p-column>
                                <p-column header="Username">
                                    <template #body="{data}">
                                        {{ data.user.username }}
                                    </template>
                                </p-column>
                                <p-column header="Email">
                                    <template #body="{data}">
                                        {{ data.user.email.toLowerCase() }}
                                    </template>
                                </p-column>
                                <p-column header="Position">
                                    <template #body="{data}">
                                        {{ data.user.role_name }}
                                    </template>
                                </p-column>
                                <p-column field="created_at" header="Date Created"></p-column>
                            </p-table>
                        </div>

                        <p-paginator
                            v-if="table.total > 0"
                            v-model:first="table.first"
                            @page="onPaginate"
                            :template="{
                                '640px': 'PrevPageLink CurrentPageReport NextPageLink',
                                default: 'FirstPageLink PrevPageLink CurrentPageReport PageLinks NextPageLink LastPageLink'
                            }"
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
            class="account-modal"
            v-model:visible="dialog.show"
            :style="{
                'width': '100%',
                'max-width': '500px'
            }"
        >
            <template #header>
                <h3 v-text="dialog.title"></h3>
            </template>
            
            <AccountForm @success="successEvent" :item="dialog.form" @close="dialog.show = !1" v-if="!dialog.loading"/>

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

        <p-toast />
        <p-confirm></p-confirm>
    </div>
</template>
  
<script>
    import AccountForm from './form';
    import AccountService  from '../../services/account';

    export default {
        components: { AccountForm },
        data() {
            return {
                breadcrumbs: [
                    { current: false, title: 'Home', url: 'dashboard' }
                ],

                page: {
                    title: "Accounts",
                    route: "account",
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
                    },
                },

                dialog: {
                    title: "",
                    show:!1,
                    loading: !1,
                    form_template: {
                        id: null,
                        first_name: null,
                        middle_name: null,
                        last_name: null,
                        username: null,
                        password: null,
                        confirm_password: null,
                        email: null,
                        avatar: null,
                        image: null
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
        },

        methods: {
            onPaginate(e) {
                if(this.table.loading) return;
                this.table.current_page = e.page + 1;
                this.getTableData(this.table.current_page);
            },
            
            getTableData(page) {
                if (this.table.loading) return;
                this.table.loading = true;

                AccountService.list(this.table.params)
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
                this.dialog.form = this.dialog.form_template;
                this.dialog.loading = !1;
                this.dialog.title = "Create";
                this.dialog.show = !0;
                // this.dialog.form.reset();
            },

            deleteData(item){
                this.$confirm.require({
                    message: 'Are you sure to delete this record?',
                    header: 'Delete Confirmation',
                    icon: 'pi pi-info-circle',
                    acceptClass: 'p-button-danger btn btn-light',
                    rejectClass: 'p-button-danger btn btn-danger',
                    accept: () => {
                    AccountService.remove(item.account_number)
                    .then((response) => {
                        this.swalMessage("success",response.data.message,"Okay",false,false,false);
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

            viewData(item){
            this.dialog.title = "Update account";
            this.dialog.data.reset();
            // this.dialog.data.clearErrors();

            AccountService.get(item.account_number)
            .then((response) => {
                let data = response.data.data;
                this.dialog.data.stepper = 1;
                this.dialog.data.id = data.account_number; 
                this.dialog.data.uid = data.user.id;
                this.dialog.data.first_name = data.first_name;
                this.dialog.data.middle_name = data.middle_name;
                this.dialog.data.last_name = data.last_name;
                this.dialog.data.role = data.user.role_name;
                this.dialog.data.username = data.user.username;
                this.dialog.data.email = data.user.email;
                this.dialog.data.avatar = data.avatar;
                this.dialog.data.password = null;
                this.dialog.data.region = data.region.map(e=> e.region_code);
                this.dialog.data.service_provider = (data.service_provider) ? data.service_provider : [];
                this.dialog.show = true;
            })
            .catch((errors) => {
                try { 
                    this.getError(errors);
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
        }
    };
</script>
