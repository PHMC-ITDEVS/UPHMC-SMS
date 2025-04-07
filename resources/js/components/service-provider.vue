<template>
    <div>
        <div class="d-flex flex-wrap align-center">
        <v-text-field
            v-model="table.search"
            prepend-inner-icon="mdi-magnify"
            label="search"
            single-line
            dense
            clearable
            hide-details="auto"
            class="py-4"
            solo
            style="max-width: 300px"
        />
        </div>
        <v-data-table
            :items="table.data"
            :headers="table.headers"
            :options.sync="table.options"
            :server-items-length="table.total"
            :loading="table.isLoading"
            :sort-by.sync="table.sortBy"
            :sort-desc.sync="table.sortDesc"
            class="elevation-0"
            >
            <template #[`item.index`]="{ index }">
                {{ (table.options.page - 1) * table.options.itemsPerPage + index + 1 }}
            </template>
            <template #[`item.logo`]="{ item }">
                <img class="tbl-img" :src="item.logo+ '?'+ Math.random()"/>
            </template>
            <template #[`item.action`]="{ item }">
                <v-btn x-small color="cyan" dark @click="selectItem(item)">
                    <v-icon small> mdi-hand-pointing-left </v-icon>
                </v-btn>
            </template>
        </v-data-table>
    </div>
</template>
<script>
export default {
    props: ["src"],
    computed: {
        options()
        {
            return this.table.options;
        },
        search()
        {
            return this.table.search;
        }
    },
    data(){
        return {
            table:{
                sortBy: 'created_at',
                sortDesc: true,
                headers: [
                    { text: "No", value: "index", sortable: false },
                    { text: "Logo", value: "logo", sortable: false },
                    { text: "Company Name", value: "company_name", sortable: false },
                    { text: "Created At", value: "created_at" },
                    { text: "", value: "action", sortable: false },
                ],
                options:{},
                search:null,
                params:{},
                isLoading: false,
                data:[],
                total:0
            },
        };
    },
    mounted()
    {
        this.updateTable();
    },
    methods: {
        selectFile(){
            this.$refs.file.click();
        },
        updateTable(loadingTable=true) {
            this.table.isLoading = loadingTable
            let params = this.table.params;
            axios.get(route("service-provider.list"),{params}).then(e=>{
                this.table.data = e.data.data.data;
                this.table.total = e.data.total;
                this.table.isLoading = false;
            });
        },

        selectItem(item)
        {
            this.$emit('setSelectedProvider', item);
        }
    
    },

    watch:{
        options: function (val) {
            this.table.params.page = val.page;
            this.table.params.page_size = val.itemsPerPage;
            if (val.sortBy.length != 0) {
            this.table.params.sort_by = val.sortBy[0];
            this.table.params.order_by = val.sortDesc[0] ? "desc" : "asc";
            } else {
            this.table.params.sort_by = null;
            this.table.params.order_by = null;
            }
            this.updateTable();
        },
        search: function (val) {
            this.table.params.search = val;
            this.updateTable();
        }, 
    }
};    
</script>
<style scoped>
</style>