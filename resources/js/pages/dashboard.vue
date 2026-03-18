<template>
  <app-layout>
    <app-breadcrumb
      :title="page.title"
      :links="breadcrumbs"
    />

    <template v-if="isAdmin">
      <div class="row">
        <div class="col-md-4" v-for="card in loginCards" :key="card.label">
          <div class="card">
            <div class="card-body">
              <span class="text-muted d-block mb-2">{{ card.label }}</span>
              <h2 class="mb-0">{{ formatNumber(card.value) }}</h2>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-xl-6">
          <div class="row">
            <div class="col-md-6" v-for="card in smsCards.slice(0, 2)" :key="card.label">
              <div class="card">
                <div class="card-body">
                  <span class="text-muted d-block mb-2">{{ card.label }}</span>
                  <h3 class="mb-0">{{ formatNumber(card.value) }}</h3>
                </div>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-header border-bottom">
              <h3 class="card-title mb-0">Daily Logins</h3>
            </div>
            <div class="card-body">
              <apexchart
                type="bar"
                height="300"
                :options="loginChartOptions"
                :series="loginChartSeries"
              />
            </div>
          </div>
        </div>

        <div class="col-xl-6">
          <div class="row">
            <div class="col-md-6" v-for="card in smsCards.slice(-2)" :key="card.label">
              <div class="card">
                <div class="card-body">
                  <span class="text-muted d-block mb-2">{{ card.label }}</span>
                  <h3 class="mb-0">{{ formatNumber(card.value) }}</h3>
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header border-bottom">
              <h3 class="card-title mb-0">Daily SMS Usage</h3>
            </div>
            <div class="card-body">
              <apexchart
                type="line"
                height="320"
                :options="smsChartOptions"
                :series="smsChartSeries"
              />
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header border-bottom">
              <h3 class="card-title mb-0">SMS Usage Per Department</h3>
            </div>
            <div class="table-responsive p-3">
              <p-table
                :value="stats.sms_by_department"
                responsiveLayout="scroll"
              >
                <template #empty>No department usage data available.</template>
                <p-column field="department" header="Department" />
                <p-column field="total_messages" header="SMS Batches" />
                <p-column field="total_recipients" header="Recipients" />
              </p-table>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="row" v-if="stats.has_department">
        <div class="col-md-4" v-for="card in departmentCards" :key="card.label">
          <div class="card">
            <div class="card-body">
              <span class="text-muted d-block mb-2">{{ card.label }}</span>
              <h2 class="mb-0">{{ formatNumber(card.value) }}</h2>
            </div>
          </div>
        </div>
      </div>

      <div class="row" v-if="stats.has_department">
        <div class="col-xl-7">
          <div class="card">
            <div class="card-header border-bottom">
              <div>
                <h3 class="card-title mb-1">Department SMS Usage</h3>
                <small class="text-muted">{{ stats.department_name }}</small>
              </div>
            </div>
            <div class="card-body">
              <apexchart
                type="area"
                height="320"
                :options="departmentChartOptions"
                :series="departmentChartSeries"
              />
            </div>
          </div>
        </div>

        <div class="col-xl-5">
          <div class="card h-100">
            <div class="card-header border-bottom">
              <div>
                <h3 class="card-title mb-1">Department View</h3>
                <small class="text-muted" v-if="stats.viewer_role === 'manager'">
                  Focused on staff SMS activity in your department.
                </small>
                <small class="text-muted" v-else>
                  Showing fellow staff SMS activity in your department.
                </small>
              </div>
            </div>
            <div class="card-body">
              <p class="mb-0 text-muted">
                This dashboard is scoped to <strong>{{ stats.department_name }}</strong>. It reflects department SMS activity and staff usage only.
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="row" v-if="stats.has_department">
        <div class="col-12">
          <div class="card">
            <div class="card-header border-bottom">
              <h3 class="card-title mb-0">Staff SMS Activity</h3>
            </div>
            <div class="table-responsive p-3">
              <p-table
                :value="stats.staff_sms_usage"
                responsiveLayout="scroll"
              >
                <template #empty>No staff usage data available for this department.</template>
                <p-column header="Name">
                  <template #body="{ data }">
                    <div>
                      <div>{{ data.name }}</div>
                      <small class="text-muted">{{ data.username }}</small>
                    </div>
                  </template>
                </p-column>
                <p-column field="role_name" header="Role" />
                <p-column field="total_messages" header="SMS Batches" />
                <p-column field="total_recipients" header="Recipients" />
                <p-column field="last_used_at" header="Last Used" />
              </p-table>
            </div>
          </div>
        </div>
      </div>

      <div class="row" v-else>
        <div class="col-lg-8">
          <div class="card">
            <div class="card-body">
              <span class="badge badge-primary mb-3">Manager / Staff Dashboard</span>
              <h2 class="mb-2">Department dashboard is waiting for assignment.</h2>
              <p class="text-muted mb-0">
                {{ stats.message }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </app-layout>
</template>

<script>
export default {
    props: {
        dashboard_variant: {
            type: String,
            default: 'staff',
        },
        dashboard_stats: {
            type: Object,
            default: () => ({}),
        },
    },

    data(){
        return {
            page: {
                title:"Dashboard",
                route:"dashboard",
                interval:null,
                tooltip:false,
            },
            breadcrumbs: [
                { current: false, title: 'Home', url: 'dashboard' }
            ],
        }
    },

    computed: {
        isAdmin() {
            return this.dashboard_variant === 'admin';
        },

        stats() {
            return this.dashboard_stats || {};
        },

        loginCards() {
            return [
                {
                    label: 'Logins Today',
                    value: this.stats.login_summary?.today ?? 0,
                },
                {
                    label: 'Logins Last 7 Days',
                    value: this.stats.login_summary?.last_7_days ?? 0,
                },
                {
                    label: 'Active Accounts Last 7 Days',
                    value: this.stats.login_summary?.active_accounts ?? 0,
                },
            ];
        },

        smsCards() {
            return [
                {
                    label: 'SMS Today',
                    value: this.stats.sms_summary?.today_messages ?? 0,
                },
                {
                    label: 'Recipients Today',
                    value: this.stats.sms_summary?.today_recipients ?? 0,
                },
                {
                    label: 'SMS Last 7 Days',
                    value: this.stats.sms_summary?.last_7_days_messages ?? 0,
                },
                {
                    label: 'Recipients Last 7 Days',
                    value: this.stats.sms_summary?.last_7_days_recipients ?? 0,
                },
            ];
        },

        departmentCards() {
            return [
                {
                    label: 'Department SMS Today',
                    value: this.stats.department_sms_summary?.today_messages ?? 0,
                },
                {
                    label: 'Department Recipients Today',
                    value: this.stats.department_sms_summary?.today_recipients ?? 0,
                },
                {
                    label: 'Active Staff Senders',
                    value: this.stats.department_sms_summary?.active_staff_senders ?? 0,
                },
            ];
        },

        chartCategories() {
            return (this.stats.login_daily || []).map(item => item.label);
        },

        loginChartSeries() {
            return [
                {
                    name: 'Logins',
                    data: (this.stats.login_daily || []).map(item => item.count),
                },
            ];
        },

        loginChartOptions() {
            return {
                chart: {
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                },
                colors: ['#0f6d31'],
                dataLabels: { enabled: false },
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        columnWidth: '42%',
                    },
                },
                xaxis: {
                    categories: this.chartCategories,
                },
                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                },
                grid: {
                    borderColor: '#edf2f7',
                },
            };
        },

        smsChartSeries() {
            return [
                {
                    name: 'Recipients',
                    data: (this.stats.sms_daily || []).map(item => item.recipients),
                },
                {
                    name: 'SMS Batches',
                    data: (this.stats.sms_daily || []).map(item => item.messages),
                },
            ];
        },

        smsChartOptions() {
            return {
                chart: {
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                },
                colors: ['#1d4ed8', '#38bdf8'],
                dataLabels: { enabled: false },
                stroke: {
                    curve: 'smooth',
                    width: [3, 3],
                },
                markers: {
                    size: 4,
                },
                xaxis: {
                    categories: (this.stats.sms_daily || []).map(item => item.label),
                },
                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                },
                grid: {
                    borderColor: '#edf2f7',
                },
                legend: {
                    position: 'top',
                },
            };
        },

        departmentChartSeries() {
            return [
                {
                    name: 'Recipients',
                    data: (this.stats.department_sms_daily || []).map(item => item.recipients),
                },
                {
                    name: 'SMS Batches',
                    data: (this.stats.department_sms_daily || []).map(item => item.messages),
                },
            ];
        },

        departmentChartOptions() {
            return {
                chart: {
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                },
                colors: ['#0f6d31', '#38bdf8'],
                dataLabels: { enabled: false },
                stroke: {
                    curve: 'smooth',
                    width: [3, 3],
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.25,
                        opacityTo: 0.05,
                        stops: [0, 90, 100],
                    },
                },
                xaxis: {
                    categories: (this.stats.department_sms_daily || []).map(item => item.label),
                },
                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                },
                grid: {
                    borderColor: '#edf2f7',
                },
                legend: {
                    position: 'top',
                },
            };
        },
    },

    mounted() {
        this.breadcrumbs.push({
            current: true,
            title: this.page.title,
            url: `${this.page.route}`
        });
    },

    methods: {
    },
}
</script>
