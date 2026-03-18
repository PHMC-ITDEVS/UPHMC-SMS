<template>
    <div>
        <app-layout>
            <app-breadcrumb
                :title="page.title"
                :links="breadcrumbs"
            ></app-breadcrumb>

            <div class="row">
                <div class="contact-group-container">
                    <div class="card contact-group-listing">
                        <div class="search-field contact-group">
                            <p-input-text
                                v-model="table.params.search"
                                class="form-control shadow-none search-override"
                                placeholder="Search"
                            />
                            <i class="search-icon pi pi-search"/>
                        </div>

                        <!-- <div class="d-flex flex-row flex-wrap align-items-center gap-2">
                           
                            <div class="button-container">
                                <p-button
                                    class="btn btn-primary"
                                    label="Add Group"
                                    icon="pi pi-plus"
                                    @click="create"
                                />
                            </div>
                        </div> -->

                        <div
                            ref="groupItems"
                            class="group-items"
                            @scroll.passive="onScroll"
                        >
                            <div class="group-item">
                                <div class="d-flex flex-row align-item-center">
                                    <span class="mdi mdi-plus"></span>
                                    <div 
                                        @click.prevent="create"
                                        class="w-100"
                                    >
                                        <h4 class="mb-0">Add Group</h4>
                                        <span class="text-muted small">Adding a group in the list.</span>
                                    </div>
                                </div>
                            </div>

                            <div class="group-item" v-if="!table.loading && table.data.length === 0">
                                <div class="d-flex flex-column align-item-center">
                                    <h4 class="text-muted">No Group</h4>
                                    <span class="text-muted small">There is no existing group yet, add a group.</span>
                                </div>
                            </div>

                            <div
                                class="group-item"
                                :class="{ active: selected_group?.id === data.id }"
                                v-for="data in table.data"
                                v-else
                            >
                                <div
                                    class="d-flex flex-row align-center justify-content-between w-100"
                                    @click.prevent="selectGroup(data)"
                                >
                                    <div class="group-item-content">
                                        <h4>{{ data.name }}</h4>
                                        <span class="text-muted small text-truncate">
                                            {{ data?.description ?? 'No description'}}
                                        </span>
                                    </div>
                                    <div class="group-item-buttons">
                                        <p-button
                                            class="btn btn-info btn-sm"
                                            icon="pi pi-pencil"
                                            @click.stop="viewData(data)"
                                        />
                                        <p-button
                                            class="btn btn-sm btn-danger"
                                            icon="pi pi-trash"
                                            @click.stop="deleteData(data)"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="group-item" v-if="table.loading && table.data.length > 0">
                                <div class="d-flex flex-column align-item-center">
                                    <span class="text-muted small">Loading more groups...</span>
                                </div>
                            </div>
                        </div>
                    </div> 
                    <div class="card contact-group-content">
                        <div class="no-seleted-group" v-if="!selected_group && !selected_group_loading">
                            <div class="empty-group-state">
                                <div class="empty-group-icon">
                                    <i class="mdi mdi-account-multiple-outline"></i>
                                </div>

                                <div class="empty-group-copy">
                                    <span class="eyebrow">Group Preview</span>
                                    <h3>No selected group</h3>
                                    <p>
                                        Pick a group from the list to view its members and details, or create a new one to get started.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="selected-group-state" v-else-if="selected_group">
                            <div class="selected-group-header">
                                <div>
                                    <span class="eyebrow">Selected Group</span>
                                    <h3>{{ selected_group.name }}</h3>
                                    <p>{{ selected_group?.description ?? 'No description' }}</p>
                                </div>

                                <div class="selected-group-stats">
                                    <span>{{ selected_group.contacts_count ?? selected_group.contacts?.length ?? 0 }} contact(s)</span>
                                </div>
                            </div>

                            <div class="selected-group-actions">
                                <p-button
                                    class="btn btn-primary btn-sm"
                                    label="Add Contact"
                                    icon="pi pi-plus"
                                    @click="addContactToGroup"
                                />
                            </div>

                            <div class="selected-group-contacts" v-if="selected_group.contacts?.length">
                                <div class="contact-row" v-for="contact in selected_group.contacts" :key="contact.id">
                                    <div class="contact-avatar">{{ getInitials(contact.name) }}</div>
                                    <div class="contact-copy">
                                        <h4>{{ contact.name }}</h4>
                                        <span>{{ contact.phone_number }}</span>
                                    </div>
                                    <div class="contact-actions">
                                        <p-button
                                            class="btn btn-sm btn-danger"
                                            icon="pi pi-trash"
                                            @click="removeContactFromGroup(contact)"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="selected-group-empty" v-else>
                                <i class="mdi mdi-account-off-outline"></i>
                                <span>No contacts in this group yet.</span>
                            </div>
                        </div>

                        <div class="selected-group-loading" v-else>
                            <p-skeleton class="mb-3" height="2rem" width="12rem"></p-skeleton>
                            <p-skeleton class="mb-4" height="1rem" width="18rem"></p-skeleton>
                            <p-skeleton class="mb-2" height="3.25rem"></p-skeleton>
                            <p-skeleton class="mb-2" height="3.25rem"></p-skeleton>
                            <p-skeleton height="3.25rem"></p-skeleton>
                        </div>
                    </div>
                </div> <!-- end col -->
            </div>
        </app-layout>

        <p-dialog
            modal
            class="group-modal"
            v-model:visible="dialog.show"
            :style="{
                'width': '100%',
                'max-width': '500px'
            }"
        >
            <template #header>
                <h3 v-text="dialog.title"></h3>
            </template>
            
            <GroupForm @hideModal="successEvent" :item="dialog.form" @close="dialog.show = !1" v-if="!dialog.loading"/>

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
            class="group-contact-dialog"
            v-model:visible="contact_dialog.show"
            :style="{
                'width': '100%',
                'max-width': '900px'
            }"
        >
            <template #header>
                <div class="d-flex flex-column">
                    <h3 class="mb-1">Add Contacts</h3>
                    <span class="text-muted small" v-if="selected_group">
                        {{ selected_group.name }}
                    </span>
                </div>
            </template>

            <div class="search-set mb-3">
                <div class="search-field w-30">
                    <p-input-text
                        v-model="contact_dialog.table.params.search"
                        class="form-control shadow-none"
                        placeholder="Search contacts"
                    />
                    <i class="search-icon pi pi-search"/>
                </div>

                <div class="button-container">
                    <p-button
                        class="btn btn-primary"
                        label="Add Selected"
                        icon="pi pi-plus"
                        :disabled="contact_dialog.selected.length === 0 || contact_dialog.submitting"
                        :loading="contact_dialog.submitting"
                        @click="submitContactSelection()"
                    />
                </div>
            </div>

            <div class="table-responsive">
                <p-table
                    v-model:selection="contact_dialog.selected"
                    dataKey="id"
                    tableClass="table"
                    :rowHover="true"
                    :loading="contact_dialog.table.loading"
                    :value="contact_dialog.table.data"
                    responsiveLayout="scroll"
                >
                    <template #empty>No contacts found.</template>
                    <template #loading><app-loader/></template>

                    <p-column selectionMode="multiple" headerStyle="width: 3rem"></p-column>
                    <p-column header="Name">
                        <template #body="{data}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="contact-avatar small">{{ getInitials(data.name) }}</div>
                                <span>{{ data.name }}</span>
                            </div>
                        </template>
                    </p-column>
                    <p-column header="Phone Number">
                        <template #body="{data}">
                            +{{ data.phone_number }}
                        </template>
                    </p-column>
                    <p-column header="Added By">
                        <template #body="{data}">
                            <span class="text-capitalize">{{ data.creator?.name ?? '-' }}</span>
                        </template>
                    </p-column>
                    <p-column header="Actions" style="width: 110px">
                        <template #body="{data}">
                            <p-button
                                class="btn btn-sm btn-info"
                                icon="pi pi-plus"
                                :disabled="isContactInSelectedGroup(data.id) || contact_dialog.submitting"
                                @click="submitContactSelection([data.id])"
                            />
                        </template>
                    </p-column>
                </p-table>
            </div>

            <p-paginator
                v-if="contact_dialog.table.total > 0"
                v-model:first="contact_dialog.table.first"
                @page="onContactPaginate"
                :totalRecords="contact_dialog.table.total"
                :rows="contact_dialog.table.params.limit"
            >
                <template #start>
                    <h5 class="mb-0">
                        Showing {{ contact_dialog.table.from }} to {{ contact_dialog.table.to }} out of {{ contact_dialog.table.total }} results
                    </h5>
                </template>
            </p-paginator>
        </p-dialog>
    </div>
</template>
  
<script>
    import GroupForm from './form';
    import GroupServices  from '../../services/group';
    import PhonebookService from '../../services/phonebook';

    export default {
        components: { GroupForm },
        data() {
            return {
                breadcrumbs: [
                    { current: false, title: 'Home', url: 'dashboard' }
                ],

                page: {
                    title: "Group",
                    route: "group.index",
                    interval: null
                },

                table: {
                    loading: !1,
                    data: [],
                    first: null,
                    total: 0,
                    total_pages: 0,
                    current_page: 1,
                    has_more: true,
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
                        description: null
                    },
                    form: new Form({}),
                },

                selected_group: null,
                selected_group_loading: false,

                contact_dialog: {
                    show: false,
                    selected: [],
                    submitting: false,
                    table: {
                        loading: false,
                        data: [],
                        first: 0,
                        total: 0,
                        total_pages: 0,
                        current_page: 1,
                        from: 0,
                        to: 0,
                        params: {
                            search: "",
                            page: 1,
                            limit: 10,
                            exclude_group_id: null
                        }
                    }
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
            resetTable() {
                this.table.data = [];
                this.table.first = 0;
                this.table.total = 0;
                this.table.total_pages = 0;
                this.table.current_page = 1;
                this.table.has_more = true;
                this.table.params.page = 1;
            },

            onScroll(event) {
                const target = event.target;

                if (!target || this.table.loading || !this.table.has_more) {
                    return;
                }

                const threshold = 80;
                const reachedBottom = target.scrollTop + target.clientHeight >= target.scrollHeight - threshold;

                if (reachedBottom) {
                    this.getTableData(this.table.current_page + 1, true);
                }
            },
            
            getTableData(page = 1, append = false) {
                if (this.table.loading) return;

                if (!append && page === 1) {
                    this.resetTable();
                }

                if (append && !this.table.has_more) {
                    return;
                }

                this.table.loading = true;
                this.table.params.page = page;

                GroupServices.list(this.table.params)
                .then((response) => {
                    let data = response.data.data;
                    this.table.data = append ? [...this.table.data, ...data.data] : data.data;
                    this.table.total = data.total;
                    this.table.total_pages = data.last_page;
                    this.table.from = data.from;
                    this.table.to = data.to;
                    this.table.current_page = data.current_page;
                    this.table.has_more = data.current_page < data.last_page;

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

            create() {
                this.dialog.form = JSON.parse(JSON.stringify(this.dialog.form_template));;
                this.dialog.loading = !1;
                this.dialog.title = "Create";
                this.dialog.show = !0;
            },

            selectGroup(item) {
                if (!item?.id || this.selected_group_loading) return;

                this.selected_group_loading = true;

                GroupServices.get(item.id)
                .then((response) => {
                    this.selected_group = response.data.data;
                })
                .catch((errors) => {
                    this.selected_group = null;

                    try {
                        this.getError(errors);
                    }
                    catch(ex){ console.log(ex)}
                })
                .finally(() => {
                    this.selected_group_loading = false;
                });
            },

            deleteData(item){
                this.$confirm.require({
                    message: 'Are you sure to delete this record?',
                    header: 'Delete Confirmation',
                    icon: 'pi pi-info-circle',
                    acceptClass: 'p-button-danger btn btn-light',
                    rejectClass: 'p-button-danger btn btn-danger',
                    accept: () => {
                    GroupServices.remove(item.id)
                    .then((response) => {
                        this.$toast.add({ severity: 'success', summary: 'Success!', detail: response.data.message, life: 3000 });
                        if (this.selected_group?.id === item.id) {
                            this.selected_group = null;
                        }
                        this.getTableData(1);
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

                GroupServices.get(item.id)
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
            },

            addContactToGroup() {
                if (!this.selected_group?.id) return;

                this.contact_dialog.show = true;
                this.contact_dialog.selected = [];
                this.contact_dialog.table.params.exclude_group_id = this.selected_group.id;
                this.contact_dialog.table.params.page = 1;
                this.contact_dialog.table.first = 0;
                this.getContactTableData(1);
            },

            onContactPaginate(e) {
                if (this.contact_dialog.table.loading) return;

                const page = e.page + 1;
                this.contact_dialog.table.current_page = page;
                this.getContactTableData(page);
            },

            getContactTableData(page = 1) {
                if (this.contact_dialog.table.loading) return;

                this.contact_dialog.table.loading = true;
                this.contact_dialog.table.params.page = page;

                PhonebookService.list(this.contact_dialog.table.params)
                .then((response) => {
                    const data = response.data.data;
                    this.contact_dialog.table.data = data.data;
                    this.contact_dialog.table.total = data.total;
                    this.contact_dialog.table.total_pages = data.last_page;
                    this.contact_dialog.table.current_page = data.current_page;
                    this.contact_dialog.table.from = data.from;
                    this.contact_dialog.table.to = data.to;

                    if (page === 1) {
                        this.contact_dialog.table.first = 0;
                    }
                })
                .catch((errors) => {
                    try {
                        this.getError(errors);
                    }
                    catch(ex){ console.log(ex)}
                })
                .finally(() => {
                    this.contact_dialog.table.loading = false;
                });
            },

            submitContactSelection(contactIds = null) {
                if (!this.selected_group?.id || this.contact_dialog.submitting) return;

                const ids = (contactIds ?? this.contact_dialog.selected.map((contact) => contact.id))
                    .filter((id) => !this.isContactInSelectedGroup(id));

                if (ids.length === 0) {
                    this.$toast.add({
                        severity: 'info',
                        summary: 'No new contacts',
                        detail: 'Selected contacts are already in this group.',
                        life: 2500
                    });
                    return;
                }

                this.contact_dialog.submitting = true;

                GroupServices.addContacts(this.selected_group.id, { contact_ids: ids })
                .then((response) => {
                    this.selected_group = response.data.data;
                    this.contact_dialog.selected = [];
                    this.contact_dialog.show = false;
                    this.$toast.add({
                        severity: 'success',
                        summary: 'Success!',
                        detail: response.data.message,
                        life: 3000
                    });
                    this.refreshGroupListItem(this.selected_group);
                })
                .catch((errors) => {
                    try {
                        this.getError(errors);
                    }
                    catch(ex){ console.log(ex)}
                })
                .finally(() => {
                    this.contact_dialog.submitting = false;
                });
            },

            isContactInSelectedGroup(contactId) {
                return !!this.selected_group?.contacts?.some((contact) => contact.id === contactId);
            },

            refreshGroupListItem(group) {
                const index = this.table.data.findIndex((item) => item.id === group.id);

                if (index === -1) return;

                this.table.data[index] = {
                    ...this.table.data[index],
                    contacts_count: group.contacts_count,
                };
            },

            removeContactFromGroup(contact) {
                if (!this.selected_group?.id || !contact?.id) return;

                this.$confirm.require({
                    message: `Remove ${contact.name} from this group?`,
                    header: 'Remove Contact',
                    icon: 'pi pi-info-circle',
                    acceptClass: 'p-button-danger btn btn-danger',
                    rejectClass: 'p-button-secondary btn btn-light',
                    accept: () => {
                        GroupServices.removeContact(this.selected_group.id, contact.id)
                        .then((response) => {
                            this.selected_group = response.data.data;
                            this.refreshGroupListItem(this.selected_group);
                            this.$toast.add({
                                severity: 'success',
                                summary: 'Success!',
                                detail: response.data.message,
                                life: 3000
                            });
                        })
                        .catch((errors) => {
                            try {
                                this.getError(errors);
                            }
                            catch(ex){ console.log(ex)}
                        });
                    }
                });
            },

            getInitials(name) {
                if (!name) return '?';

                return name
                    .split(' ')
                    .filter(Boolean)
                    .slice(0, 2)
                    .map((part) => part[0].toUpperCase())
                    .join('');
            }
        },

        watch: {
            "table.params.search": function (val) {
                this.getTableData(1);
            },

            "contact_dialog.table.params.search": function () {
                if (!this.contact_dialog.show) return;

                this.contact_dialog.table.first = 0;
                this.getContactTableData(1);
            },

            "contact_dialog.show": function (value) {
                if (value) return;

                this.contact_dialog.selected = [];
                this.contact_dialog.table.params.search = "";
                this.contact_dialog.table.params.page = 1;
                this.contact_dialog.table.first = 0;
            }
        }
    };
</script>
