<template>
    <div>
        <app-layout>
            <app-breadcrumb
                :title="page.title"
                :links="breadcrumbs"
            />

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
                                    label="How To Use"
                                    icon="pi pi-question-circle"
                                    @click="help.show = true"
                                />
                                <p-button
                                    class="btn btn-primary"
                                    label="Add API Client"
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
                                        <div class="d-flex flex-column">
                                            <strong>{{ data.name }}</strong>
                                            <small class="text-muted">{{ data.client_key }}</small>
                                        </div>
                                    </template>
                                </p-column>
                                <p-column header="Department">
                                    <template #body="{data}">
                                        {{ data.department?.name ?? 'Global' }}
                                    </template>
                                </p-column>
                                <p-column header="Status">
                                    <template #body="{data}">
                                        <span class="badge" :class="data.status === 'active' ? 'success' : 'secondary'">
                                            {{ data.status }}
                                        </span>
                                    </template>
                                </p-column>
                                <p-column header="Last Used">
                                    <template #body="{data}">
                                        {{ data.last_used_at ?? '-' }}
                                    </template>
                                </p-column>
                                <p-column field="created_at" header="Date Created"></p-column>
                                <p-column header="Actions" style="width: 140px">
                                    <template #body="{data}">
                                        <div class="d-flex flex-row gap-2 actions-menu">
                                            <p-button
                                                class="btn btn-sm info"
                                                icon="pi pi-pencil"
                                                @click="viewData(data)"
                                            />
                                            <p-button
                                                class="btn btn-sm warning"
                                                icon="pi pi-refresh"
                                                @click="regenerateSecret(data)"
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
                </div>
            </div>
        </app-layout>

        <p-dialog
            modal
            class="phonebook-modal"
            v-model:visible="dialog.show"
            :style="{ width: '100%', maxWidth: '600px' }"
        >
            <template #header>
                <h3 v-text="dialog.title"></h3>
            </template>

            <ApiClientForm
                @hideModal="successEvent"
                :item="dialog.form"
                @close="dialog.show = !1"
                v-if="!dialog.loading"
            />

            <div class="skeleton-cont" v-else>
                <p-skeleton class="mt-5 mb-4" height="1.3rem" borderRadius="16px"></p-skeleton>
                <p-skeleton height="1.5rem" width="10rem" class="mb-4" borderRadius="16px"></p-skeleton>
                <p-skeleton width="5rem" class="mb-2" borderRadius="16px"></p-skeleton>
                <p-skeleton height="2.25rem" class="mb-4"></p-skeleton>
                <p-skeleton width="5rem" class="mb-2" borderRadius="16px"></p-skeleton>
                <p-skeleton height="2.25rem" class="mb-4"></p-skeleton>
            </div>
        </p-dialog>

        <p-dialog
            modal
            v-model:visible="credentials.show"
            :style="{ width: '100%', maxWidth: '700px' }"
        >
            <template #header>
                <h3>API Credentials</h3>
            </template>

            <div class="d-flex flex-column gap-3">
                <div class="alert alert-warning mb-0">
                    Save these credentials now. The raw secret will not be shown again.
                </div>

                <div>
                    <label class="d-block mb-2 font-weight-bold">Client Key</label>
                    <p-input-text :modelValue="credentials.data.client_key" class="form-control shadow-none" readonly />
                </div>

                <div>
                    <label class="d-block mb-2 font-weight-bold">Client Secret</label>
                    <p-input-text :modelValue="credentials.data.client_secret" class="form-control shadow-none" readonly />
                </div>

                <div>
                    <label class="d-block mb-2 font-weight-bold">Bearer Token</label>
                    <p-textarea :modelValue="credentials.data.bearer_token" class="form-control shadow-none" rows="4" readonly />
                </div>
            </div>
        </p-dialog>

        <p-dialog
            modal
            v-model:visible="help.show"
            :style="{ width: '100%', maxWidth: '760px' }"
        >
            <template #header>
                <h3>How To Use SMS API</h3>
            </template>

            <div class="d-flex flex-column gap-3">
                <div class="alert alert-info mb-0">
                    Create an API client, copy the one-time bearer token, then use it from your external system.
                </div>

                <div>
                    <h4 class="mb-2">Flow</h4>
                    <ol class="mb-0 pl-3">
                        <li>Create an API client from this module.</li>
                        <li>Copy the generated bearer token immediately.</li>
                        <li>Send SMS via <code>POST /api/v1/sms/send</code>.</li>
                        <li>Track delivery via <code>GET /api/v1/sms/{id}/status</code>.</li>
                    </ol>
                </div>

                <div>
                    <h4 class="mb-2">Headers</h4>
                    <pre class="bg-light border rounded-3 p-3 mb-0"><code>Authorization: Bearer CLIENTKEY.RAWSECRET
Accept: application/json
Content-Type: application/json</code></pre>
                </div>

                <div>
                    <h4 class="mb-2">Send Request Example</h4>
                    <pre class="bg-light border rounded-3 p-3 mb-0"><code>curl -X POST http://your-app.test/api/v1/sms/send \
  -H "Authorization: Bearer CLIENTKEY.RAWSECRET" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "body": "Hello from API",
    "recipients": [
      { "type": "number", "value": "09171234567" }
    ],
    "send_type": "immediate"
  }'</code></pre>
                </div>

                <div>
                    <h4 class="mb-2">Status Request Example</h4>
                    <pre class="bg-light border rounded-3 p-3 mb-0"><code>curl -X GET http://your-app.test/api/v1/sms/123/status \
  -H "Authorization: Bearer CLIENTKEY.RAWSECRET" \
  -H "Accept: application/json"</code></pre>
                </div>
            </div>
        </p-dialog>
    </div>
</template>

<script>
import ApiClientForm from './form';
import ApiClientServices from '../../services/api-client';

export default {
    components: { ApiClientForm },

    data() {
        return {
            breadcrumbs: [
                { current: false, title: 'Home', url: 'dashboard' }
            ],

            page: {
                title: "API Clients",
                route: "api-client.index",
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
                title: "",
                show: !1,
                loading: !1,
                form_template: {
                    id: null,
                    name: null,
                    department_id: null,
                    status: 'active',
                    allowed_ips: null,
                    description: null,
                },
                form: new Form({}),
            },

            credentials: {
                show: !1,
                data: {
                    client_key: null,
                    client_secret: null,
                    bearer_token: null,
                }
            },

            help: {
                show: !1,
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

            ApiClientServices.list(this.table.params)
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
                catch (ex) {}
            })
            .finally(() => {
                this.table.loading = false;
            });
        },

        create() {
            this.dialog.form = JSON.parse(JSON.stringify(this.dialog.form_template));
            this.dialog.loading = !1;
            this.dialog.title = "Create";
            this.dialog.show = !0;
        },

        viewData(item) {
            this.dialog.title = `Modify Record`;
            this.dialog.form = this.dialog.form_template;
            this.dialog.loading = !0;
            this.dialog.show = !0;

            ApiClientServices.get(item.id)
            .then((response) => {
                const data = response.data.data;

                this.dialog.form = {
                    id: data.id,
                    name: data.name,
                    department_id: data.department_id,
                    status: data.status,
                    allowed_ips: (data.allowed_ips || []).join('\n'),
                    description: data.meta?.description ?? null,
                };
            })
            .catch((errors) => {
                this.dialog.show = !1;
                try {
                    this.getError(errors);
                }
                catch (ex) {}
            })
            .finally(() => {
                this.dialog.loading = !1;
            });
        },

        regenerateSecret(item) {
            this.$confirm.require({
                message: 'Regenerate this API client secret?',
                header: 'Regenerate Secret',
                icon: 'pi pi-info-circle',
                acceptClass: 'p-button-danger btn btn-light',
                rejectClass: 'p-button-danger btn btn-danger',
                accept: () => {
                    ApiClientServices.regenerateSecret(item.id)
                    .then((response) => {
                        this.$toast.add({ severity: 'success', summary: 'Success!', detail: response.data.message, life: 3000 });
                        this.showCredentials(response.data.credentials);
                    })
                    .catch((errors) => {
                        try {
                            this.getError(errors);
                        }
                        catch (ex) {}
                    });
                }
            });
        },

        deleteData(item) {
            this.$confirm.require({
                message: 'Are you sure to delete this record?',
                header: 'Delete Confirmation',
                icon: 'pi pi-info-circle',
                acceptClass: 'p-button-danger btn btn-light',
                rejectClass: 'p-button-danger btn btn-danger',
                accept: () => {
                    ApiClientServices.remove(item.id)
                    .then((response) => {
                        this.$toast.add({ severity: 'success', summary: 'Success!', detail: response.data.message, life: 3000 });
                        this.getTableData(this.table.current_page);
                    })
                    .catch((errors) => {
                        try {
                            this.getError(errors);
                        }
                        catch (ex) {}
                    });
                }
            });
        },

        successEvent(response) {
            this.dialog.show = !1;
            this.getTableData(this.table.current_page);

            if (response?.credentials) {
                this.showCredentials(response.credentials);
            }
        },

        showCredentials(credentials) {
            this.credentials.data = {
                client_key: credentials.client_key,
                client_secret: credentials.client_secret,
                bearer_token: credentials.bearer_token,
            };
            this.credentials.show = !0;
        },
    }
}
</script>
