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
                                    class="btn btn-light"
                                    label="Import"
                                    icon="pi pi-upload"
                                    @click="openImportDialog"
                                />
                                <p-button
                                    class="btn btn-primary"
                                    label="Add Contact"
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
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <p-image :src="data.avatar" :alt="data.name" width="30" height="30" preview/>
                                            <span class="text-capitalize">{{ data.name }}</span>
                                        </div>
                                        
                                    </template>
                                </p-column>
                                <p-column header="Phone Number">
                                    <template #body="{data}">
                                        +{{ data.phone_number }}
                                    </template>
                                </p-column>
                                <p-column header="Notes">
                                    <template #body="{data}">
                                        {{ data.notes ?? '-' }}
                                    </template>
                                </p-column>
                                <p-column header="Added By" v-if="_is_admin">
                                    <template #body="{data}">
                                        <span class="text-capitalize">{{ data.creator?.name ?? '-' }}</span>
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
            
            <PhonebookForm @hideModal="successEvent" :item="dialog.form" @close="dialog.show = !1" v-if="!dialog.loading"/>

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

        <p-dialog
            modal
            class="import-contacts-dialog"
            v-model:visible="import_dialog.show"
            :style="{ width: '100%', maxWidth: '520px' }"
        >
            <template #header>
                <div class="import-dialog-header-copy">
                    <span class="import-dialog-eyebrow">Bulk Upload</span>
                    <h3 class="mb-0">Import Contacts</h3>
                </div>
            </template>

            <div class="import-dialog-body">
                <div class="import-dialog-intro">
                    <div class="import-dialog-icon">
                        <i class="pi pi-upload"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1">
                            Upload a CSV or Excel file using the contact template.
                        </p>
                        <p class="import-dialog-columns mb-0">
                            Required columns: <strong>name</strong>, <strong>phone_number</strong>, <strong>notes</strong>.
                        </p>
                    </div>
                </div>

                <div class="import-dialog-template">
                    <a
                        class="btn btn-light import-template-btn"
                        href="/phonebook/template"
                    >
                        <i class="pi pi-download mr-2"></i>
                        Download Template
                    </a>
                    <small class="text-muted d-block mt-2">Use the template if you want the exact accepted format.</small>
                </div>

                <div class="import-upload-surface" @click="$refs.import_file.click()">
                    <div class="import-upload-icon secondary">
                        <i class="pi pi-file-import"></i>
                    </div>
                    <div class="import-upload-copy">
                        <h4>{{ import_dialog.file ? import_dialog.file.name : 'Choose a file to import' }}</h4>
                        <p>{{ import_dialog.file ? formatFileSize(import_dialog.file.size) : 'CSV, XLS, or XLSX up to your server upload limit' }}</p>
                    </div>
                    <p-button
                        class="btn btn-light import-browse-btn"
                        label="Browse"
                        icon="pi pi-folder-open"
                        @click.stop="$refs.import_file.click()"
                    />
                    <input
                        ref="import_file"
                        type="file"
                        accept=".csv,.xlsx,.xls"
                        class="d-none"
                        @change="onImportFileChange"
                    />
                </div>

                <div v-if="import_dialog.summary" class="import-summary-card">
                    <div class="import-summary-grid">
                        <div>
                            <span>Imported</span>
                            <strong>{{ import_dialog.summary.imported }}</strong>
                        </div>
                        <div>
                            <span>Skipped</span>
                            <strong>{{ import_dialog.summary.skipped }}</strong>
                        </div>
                    </div>
                    <div v-if="import_dialog.summary.errors?.length" class="mt-3">
                        <strong class="d-block mb-2">Import Notes</strong>
                        <ul class="mb-0 pl-3 import-summary-list">
                            <li v-for="(error, index) in import_dialog.summary.errors.slice(0, 5)" :key="index">
                                {{ error }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <template #footer>
                <p-button
                    class="btn btn-light"
                    label="Cancel"
                    @click="closeImportDialog"
                />
                <p-button
                    class="btn btn-primary"
                    label="Import"
                    icon="pi pi-upload"
                    :loading="import_dialog.loading"
                    @click="submitImport"
                />
            </template>
        </p-dialog>
    </div>
</template>
  
<script>
    import PhonebookForm from './form';
    import PhonebookService  from '../../services/phonebook';

    export default {
        components: { PhonebookForm },
        data() {
            return {
                breadcrumbs: [
                    { current: false, title: 'Home', url: 'dashboard' }
                ],

                page: {
                    title: "Phonebook",
                    route: "phonebook.index",
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
                        phone_number: null,
                        notes: null,
                    },
                    form: new Form({}),
                },

                import_dialog: {
                    show: false,
                    loading: false,
                    file: null,
                    summary: null,
                },
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

                PhonebookService.list(this.table.params)
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

            openImportDialog() {
                this.import_dialog.show = true;
                this.import_dialog.file = null;
                this.import_dialog.summary = null;
            },

            closeImportDialog() {
                this.import_dialog.show = false;
                this.import_dialog.loading = false;
                this.import_dialog.file = null;
                this.import_dialog.summary = null;

                if (this.$refs.import_file) {
                    this.$refs.import_file.value = null;
                }
            },

            onImportFileChange(event) {
                this.import_dialog.file = event.target.files?.[0] || null;
            },

            submitImport() {
                if (this.import_dialog.loading || !this.import_dialog.file) return;

                this.import_dialog.loading = true;
                this.import_dialog.summary = null;

                const formData = new FormData();
                formData.append('file', this.import_dialog.file);

                PhonebookService.import(formData)
                    .then((response) => {
                        this.import_dialog.summary = response.data.summary;
                        this.import_dialog.file = null;

                        if (this.$refs.import_file) {
                            this.$refs.import_file.value = null;
                        }

                        this.$toast.add({
                            severity: 'success',
                            summary: 'Success!',
                            detail: response.data.message,
                            life: 3000,
                        });
                        this.getTableData(1);
                    })
                    .catch((errors) => {
                        try {
                            this.getError(errors);
                        } catch (ex) {
                            console.log(ex);
                        }
                    })
                    .finally(() => {
                        this.import_dialog.loading = false;
                    });
            },

            formatFileSize(bytes) {
                if (!bytes) return '0 B';

                const units = ['B', 'KB', 'MB', 'GB'];
                let size = bytes;
                let unitIndex = 0;

                while (size >= 1024 && unitIndex < units.length - 1) {
                    size /= 1024;
                    unitIndex++;
                }

                return `${size.toFixed(size >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
            },

            deleteData(item){
                this.$confirm.require({
                    message: 'Are you sure to delete this record?',
                    header: 'Delete Confirmation',
                    icon: 'pi pi-info-circle',
                    acceptClass: 'p-button-danger btn btn-light',
                    rejectClass: 'p-button-danger btn btn-danger',
                    accept: () => {
                    PhonebookService.remove(item.id)
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

                PhonebookService.get(item.id)
                .then((response) => {
                    let data = response.data.data;
                    let params = {...data};
                    
                    if (params.phone_number.startsWith('63')) {
                        params.phone_number = params.phone_number.substring(2);
                    }

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
