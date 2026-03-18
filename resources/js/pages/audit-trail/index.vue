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

                                <p-column header="Event">
                                    <template #body="{data}">
                                        <span class="text-capitalize">{{ data.event }}</span>
                                    </template>
                                </p-column>
                                <p-column header="Module">
                                    <template #body="{data}">
                                        {{ formatModule(data.auditable_type) }}
                                    </template>
                                </p-column>
                                <p-column header="Record ID">
                                    <template #body="{data}">
                                        {{ data.auditable_id }}
                                    </template>
                                </p-column>
                                <p-column header="User">
                                    <template #body="{data}">
                                        {{ data.user?.username ?? 'System' }}
                                    </template>
                                </p-column>
                                <p-column field="created_at" header="Date Created"></p-column>
                                <p-column header="Actions" style="width: 100px">
                                    <template #body="{data}">
                                        <div class="d-flex flex-row gap-2 actions-menu">
                                            <p-button
                                                class="btn btn-sm info"
                                                icon="pi pi-eye"
                                                @click="viewData(data)"
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
                </div>
            </div>
        </app-layout>

        <p-dialog
            modal
            class="audit-trail-modal"
            v-model:visible="dialog.show"
            :style="{
                'width': '100%',
                'max-width': '700px'
            }"
        >
            <template #header>
                <h3>Audit Trail Details</h3>
            </template>

            <div v-if="!dialog.loading && dialog.data" class="d-flex flex-column gap-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="small text-muted">Event</div>
                        <div class="fw-semibold text-capitalize">{{ dialog.data.event }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Module</div>
                        <div class="fw-semibold">{{ formatModule(dialog.data.auditable_type) }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Record ID</div>
                        <div class="fw-semibold">{{ dialog.data.auditable_id }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">User</div>
                        <div class="fw-semibold">{{ dialog.data.user?.username ?? 'System' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">IP Address</div>
                        <div class="fw-semibold">{{ dialog.data.ip_address ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Date Created</div>
                        <div class="fw-semibold">{{ dialog.data.created_at }}</div>
                    </div>
                </div>

                <div>
                    <div class="small text-muted mb-1">Old Values</div>
                    <pre class="bg-light border rounded-3 p-3 mb-0">{{ formatJson(dialog.data.old_values) }}</pre>
                </div>

                <div>
                    <div class="small text-muted mb-1">New Values</div>
                    <pre class="bg-light border rounded-3 p-3 mb-0">{{ formatJson(dialog.data.new_values) }}</pre>
                </div>
            </div>

            <div class="skeleton-cont" v-else>
                <p-skeleton class="mt-2 mb-3" height="1.3rem" borderRadius="16px"></p-skeleton>
                <p-skeleton class="mb-3" height="1.3rem" borderRadius="16px"></p-skeleton>
                <p-skeleton class="mb-3" height="7rem"></p-skeleton>
                <p-skeleton height="7rem"></p-skeleton>
            </div>
        </p-dialog>
    </div>
</template>

<script>
    import AuditTrailServices from '../../services/audit-trail';

    export default {
        data() {
            return {
                breadcrumbs: [
                    { current: false, title: 'Home', url: 'dashboard' }
                ],

                page: {
                    title: "Audit Trail",
                    route: "audit-trail.index",
                    interval: null
                },

                table: {
                    loading: !1,
                    data: [],
                    first: null,
                    total: 0,
                    total_pages: 0,
                    current_page: 1,
                    params: {
                        search: "",
                        page: 1,
                        limit: 10
                    },
                },

                dialog: {
                    show: !1,
                    loading: !1,
                    data: null,
                }
            }
        },

        mounted() {
            this.breadcrumbs.push({
                current: true,
                title: this.page.title,
                url: `${this.page.route}`
            });

            this.getTableData(1);
        },

        methods: {
            onPaginate(e) {
                if (this.table.loading) return;
                this.table.current_page = e.page + 1;
                this.getTableData(this.table.current_page);
            },

            getTableData(page) {
                if (this.table.loading) return;
                this.table.loading = true;
                this.table.params.page = page;

                AuditTrailServices.list(this.table.params)
                .then((response) => {
                    let data = response.data.data;
                    this.table.data = data.data;
                    this.table.total = data.total;
                    this.table.total_pages = data.last_page;
                    this.table.from = data.from;
                    this.table.to = data.to;

                    if (page == 1) this.table.first = 0;
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

            viewData(item) {
                this.dialog.loading = !0;
                this.dialog.show = !0;
                this.dialog.data = null;

                AuditTrailServices.get(item.id)
                .then((response) => {
                    this.dialog.data = response.data.data;
                    this.dialog.loading = false;
                })
                .catch((errors) => {
                    try {
                        this.getError(errors);
                    }
                    catch(ex){ console.log(ex)}
                });
            },

            formatModule(value) {
                if (!value) return '-';

                const segments = value.split('\\');
                return segments[segments.length - 1];
            },

            formatJson(value) {
                if (!value || (Array.isArray(value) && !value.length)) {
                    return '-';
                }

                return JSON.stringify(value, null, 2);
            }
        },

        watch: {
            "table.params.search": function () {
                this.getTableData(1);
            }
        }
    };
</script>
