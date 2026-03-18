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
                                    label="Add Role"
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
                                
                                <p-column header="Name">
                                    <template #body="{data}">
                                        <span class="text-capitalize">{{ data.name }}</span>
                                    </template>
                                </p-column>
                                <p-column header="Display Name">
                                    <template #body="{data}">
                                        {{ data.display_name }}
                                    </template>
                                </p-column>
                                <p-column header="Description">
                                    <template #body="{data}">
                                        {{ data.description ?? '-' }}
                                    </template>
                                </p-column>

                                <p-column field="created_at" header="Date Created"></p-column>
                                <p-column header="Actions" style="width: 100px">
                                    <template #body="{data}">
                                        <div class="d-flex flex-row gap-2 actions-menu">
                                            <p-button
                                                class="btn btn-sm info"
                                                icon="pi pi-pencil"
                                                @click="viewData(data)"
                                            />
                                            <p-button
                                                class="btn btn-sm danger"
                                                icon="pi pi-trash"
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
            class="phonebook-modal"
            v-model:visible="dialog.show"
            :style="{
                'width': '100%',
                'max-width': '500px'
            }"
        >
            <template #header>
                <h3 v-text="dialog.title"></h3>
            </template>
            
            <RoleForm @hideModal="successEvent" :item="dialog.form" @close="dialog.show = !1" v-if="!dialog.loading"/>

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
    import RoleForm from './form';
    import RoleServices  from '../../services/role';

    export default {
        components: { RoleForm },
        data() {
            return {
                breadcrumbs: [
                    { current: false, title: 'Home', url: 'dashboard' }
                ],

                page: {
                    title: "Role",
                    route: "role.index",
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
                        name: null,
                        display_name: null,
                        description: null,
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
                console.log(this.table.current_page);
                this.getTableData(this.table.current_page);
            },
            
            getTableData(page) {
                if (this.table.loading) return;
                this.table.loading = true;
                this.table.params.page = page;

                RoleServices.list(this.table.params)
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
                    RoleServices.remove(item.id)
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

            viewData(item){
                this.dialog.title = `Modify Record`;
                this.dialog.form = this.dialog.form_template;
                // this.dialog.form.reset();
                this.dialog.loading = !0;
                this.dialog.show = !0;

                RoleServices.get(item.id)
                .then((response) => {
                    let data = response.data.data;
                    let params = {...data};

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
        }
    };
</script>
