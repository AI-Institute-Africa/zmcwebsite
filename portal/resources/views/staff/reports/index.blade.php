@extends('layouts.portal')

@section('title', 'Operational Reports')

@section('content')
@include('staff.partials.dashboard_theme')
<div class="staff-dashboard-theme">
<div id="reportsApp" class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);" v-cloak>
    <style>
      .reports-shell{border:1px solid #e5ede9!important;border-radius:22px!important;overflow:hidden;box-shadow:0 10px 24px rgba(15,35,48,.07)}.reports-shell .card-header{background:#fff!important}.reports-shell .nav-tabs-custom{overflow:auto;flex-wrap:nowrap}.reports-shell .nav-link{white-space:nowrap;padding:13px 15px!important;border-bottom:3px solid transparent!important}.reports-shell .nav-link.active{background:#fbfdfc!important;border-bottom-color:var(--zmc-accent)!important;color:#6d5a00!important}.reports-filter{background:#f8faf9!important}.reports-filter .form-control,.reports-filter .form-select{height:40px;border-radius:10px;border-color:#dce7e1}.reports-filter .btn{height:40px;border-radius:10px;font-weight:700}.reports-summary .report-kpi{border:1px solid #e8edf1!important;border-radius:18px!important;box-shadow:0 8px 20px rgba(15,35,48,.06)!important;padding:18px!important;min-height:142px;position:relative;overflow:hidden}.reports-summary .report-kpi:after{content:"";position:absolute;width:80px;height:80px;right:-28px;bottom:-28px;border-radius:50%;background:rgba(250,204,21,.11)}.reports-summary .report-kpi-icon{width:36px;height:36px;border-radius:11px;display:grid;place-items:center;background:#fff8d9;color:#9a7900;font-size:1.05rem;position:relative;z-index:1}.reports-summary .report-kpi-label{margin-top:14px;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#64756f;font-weight:800}.reports-summary .report-kpi-value{font-size:1.8rem;line-height:1.05;font-weight:850;color:#172b2b;margin-top:4px}.reports-table{border:1px solid #e8edf1!important;border-radius:22px!important;overflow:hidden;box-shadow:0 10px 24px rgba(15,35,48,.07)!important}.reports-table .card-header{background:#fff!important}.reports-table .table thead th{background:#f4f8f6!important;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em}.reports-table .table tbody tr:hover{background:#fbfdfc}.reports-chart{border:1px solid #e8edf1!important;border-radius:22px!important;box-shadow:0 10px 24px rgba(15,35,48,.07)!important}.reports-heading{padding:18px 0 4px}.reports-heading h4{font-size:var(--font-size-2xl);font-weight:800;margin:0;color:var(--zmc-text-dark)}.reports-heading p{margin:5px 0 0;color:#71817b;font-size:var(--font-size-base)}
    </style>
    {{-- Header --}}
    <div class="reports-heading d-flex justify-content-between align-items-start flex-wrap gap-3"><div><h1>Operational Reports</h1><p>Monitor applications, payments, processing performance, levies, printing, collection, and user activity.</p></div><div class="d-flex gap-2"><button class="btn btn-white border shadow-sm btn-sm px-3" @click="reloadData"><i class="ri-refresh-line me-1"></i> Refresh</button><button class="btn btn-white border shadow-sm btn-sm px-3" @click="printReport"><i class="ri-printer-line me-1"></i> Print</button></div></div>

    {{-- Tabs --}}
    <div class="reports-shell card mb-4">
        <div class="card-header bg-white p-0 border-bottom">
            <ul class="nav nav-tabs nav-tabs-custom border-0 px-3" role="tablist">
                <li class="nav-item" v-for="(label, key) in config.tabs" :key="key">
                    <a class="nav-link border-0 py-3" :class="{ active: activeTab === key }" @click.prevent="switchTab(key)" href="#">
                        <span class="small fw-bold text-uppercase">@{{ label }}</span>
                    </a>
                </li>
            </ul>
        </div>

        {{-- Advanced Filter Panel --}}
        <div class="reports-filter card-body border-bottom" v-if="activeTab !== 'audit-logs'">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label zmc-lbl">Date Range</label>
                    <div class="input-group input-group-sm">
                        <input type="date" v-model="filters.date_from" class="form-control">
                        <span class="input-group-text bg-white">to</span>
                        <input type="date" v-model="filters.date_to" class="form-control">
                    </div>
                </div>
                
                <div class="col-md-2" v-if="['applications', 'overview'].includes(activeTab)">
                    <label class="form-label zmc-lbl">App Type</label>
                    <select v-model="filters.application_type" class="form-select form-select-sm">
                        <option v-for="(lbl, val) in config.filterOptions.application_types" :value="val">@{{ lbl }}</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label zmc-lbl">Status</label>
                    <select v-model="filters.status" class="form-select form-select-sm">
                        <option v-for="(lbl, val) in config.filterOptions.statuses" :value="val">@{{ lbl }}</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label zmc-lbl">Region</label>
                    <select v-model="filters.region" class="form-select form-select-sm">
                        <option v-for="(lbl, val) in config.filterOptions.regions" :value="val">@{{ lbl }}</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label zmc-lbl">Officer</label>
                    <select v-model="filters.officer_id" class="form-select form-select-sm">
                        <option v-for="(lbl, val) in config.filterOptions.officers" :value="val">@{{ lbl }}</option>
                    </select>
                </div>

                {{-- Second row of filters --}}
                <div class="col-md-2">
                    <label class="form-label zmc-lbl">Form Type</label>
                    <select v-model="filters.form_type" class="form-select form-select-sm">
                        <option v-for="(lbl, val) in config.filterOptions.form_types" :value="val">@{{ lbl }}</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label zmc-lbl">Payment Status</label>
                    <select v-model="filters.payment_status" class="form-select form-select-sm">
                        <option v-for="(lbl, val) in config.filterOptions.payment_statuses" :value="val">@{{ lbl }}</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label zmc-lbl">Scope</label>
                    <select v-model="filters.scope" class="form-select form-select-sm">
                        <option v-for="(lbl, val) in config.filterOptions.scopes" :value="val">@{{ lbl }}</option>
                    </select>
                </div>

                <div class="col-md-6 d-flex gap-2 justify-content-end align-items-end">
                    <button class="btn btn-primary btn-sm px-4" @click="reloadData">
                        <i class="ri-filter-3-line me-1"></i> Apply Filters
                    </button>
                    <button class="btn btn-white border btn-sm px-3" @click="resetFilters">Reset</button>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                            <i class="ri-download-line me-1"></i> Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><a class="dropdown-item" href="#" @click.prevent="exportReport('pdf')">Export PDF</a></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="exportReport('excel')">Export Excel</a></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="exportReport('csv')">Export CSV</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards Grid --}}
    <div class="reports-summary row g-3 mb-4" v-if="summaryData">
        <div class="col-12 col-sm-6 col-lg-4 col-xl-2" v-for="(val, key) in summaryData" :key="key">
            <div class="report-kpi h-100 bg-white">
                <div class="report-kpi-icon"><i :class="getSummaryIcon(key)"></i></div>
                <div class="report-kpi-label">@{{ formatSummaryKey(key) }}</div>
                <div class="report-kpi-value" :class="getSummaryColor(key)">@{{ formatValue(val, key) }}</div>
            </div>
        </div>
    </div>

    {{-- Main Data Table --}}
    <div class="reports-table zmc-card p-0 bg-white mb-4" v-if="!loading">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="fw-bold m-0 text-uppercase" style="letter-spacing: 0.05em;">@{{ config.tabs[activeTab] }} Details</h2>
            <div class="small text-muted">Showing latest @{{ tableData.length }} records</div>
        </div>
        
        <div class="p-5 text-center text-muted" v-if="tableData.length === 0">
            <i class="ri-inbox-line display-4 d-block mb-2 opacity-50"></i>
            <p>No data found for the selected criteria.</p>
        </div>

        <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0 zmc-mini-table">
                <thead>
                    <tr class="small text-muted text-uppercase bg-light">
                        <th v-for="col in columns" :key="col.key">@{{ col.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, idx) in tableData" :key="idx">
                        <td v-for="col in columns" :key="col.key">
                            <div v-if="col.key.includes('status') || col.key.includes('paid')">
                                <span class="px-2 py-1 rounded-pill small" :class="getStatusBadge(row[col.key])" style="font-weight: 400 !important;">
                                    @{{ row[col.key] }}
                                </span>
                            </div>
                            <div v-else-if="col.key === 'sla'">
                                <span class="px-2 py-1 rounded-pill small" :class="getSLABadge(row[col.key])" style="font-weight: 400 !important;">
                                    @{{ row[col.key] }}
                                </span>
                            </div>
                            <div v-else>@{{ row[col.key] }}</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="row g-4 mb-4" v-if="chartsData">
        <div class="col-md-6" v-for="(chart, key) in chartsData" :key="key">
            <div class="reports-chart zmc-card p-4 bg-white" style="border-radius: 12px;">
                <h3 class="fw-bold mb-3">@{{ formatSummaryKey(key) }}</h3>
                <div :id="'chart-' + key" style="min-height: 250px;"></div>
            </div>
        </div>
    </div>

    <div class="p-5 text-center" v-if="loading">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-2 text-muted small">Loading report data...</div>
    </div>
</div>
</div>

@push('styles')
<style>
    .nav-tabs-custom .nav-link {
        color: #64748b;
        font-weight: 600;
        border-bottom: 3px solid transparent !important;
        transition: all 0.2s ease;
    }
    .nav-tabs-custom .nav-link.active {
        color: var(--zmc-accent);
        border-bottom: 3px solid var(--zmc-accent) !important;
        background: transparent;
    }
    .zmc-lbl { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
    .zmc-mini-table th { padding: 12px 16px; border-top: 0; font-size: 10px; }
    .zmc-mini-table td { padding: 12px 16px; font-size: 12px; }
    .zmc-card:hover { transform: translateY(-3px); shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important; }
    .badge-soft-success { background: #dcfce7; color: #15803d; }
    .badge-soft-warning { background: #fef9c3; color: #854d0e; }
    .badge-soft-danger { background: #fee2e2; color: #b91c1c; }
    .badge-soft-primary { background: #e0f2fe; color: #0369a1; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js"></script>
<script>
    const { createApp, ref, onMounted, watch } = Vue;

    createApp({
        setup() {
            const config = ref({ tabs: {}, filterOptions: {} });
            const activeTab = ref('overview');
            const summaryData = ref(null);
            const tableData = ref([]);
            const chartsData = ref(null);
            const loading = ref(false);
            const filters = ref({
                date_from: '', date_to: '', application_type: '',
                status: '', region: '', officer_id: '',
                payment_status: '', scope: '', form_type: '', category: ''
            });

            const columns = ref([]);
            const columnMap = {
                'overview': [
                    { label: 'Ref', key: 'ref' },
                    { label: 'Applicant', key: 'applicant' },
                    { label: 'Type', key: 'type' },
                    { label: 'Status', key: 'status' },
                    { label: 'Date', key: 'date' }
                ],
                'applications': [
                    { label: 'Ref', key: 'ref' },
                    { label: 'Applicant', key: 'applicant' },
                    { label: 'Type', key: 'type' },
                    { label: 'Form', key: 'form' },
                    { label: 'Category', key: 'category' },
                    { label: 'Scope', key: 'scope' },
                    { label: 'Region', key: 'region' },
                    { label: 'Status', key: 'status' },
                    { label: 'Payment', key: 'payment' },
                    { label: 'Officer', key: 'officer' },
                    { label: 'Date', key: 'date' },
                    { label: 'Days', key: 'days' }
                ],
                'registration': [
                    { label: 'Ref', key: 'ref' },
                    { label: 'Media House', key: 'media_house' },
                    { label: 'Form', key: 'form' },
                    { label: 'Category', key: 'category' },
                    { label: 'Status', key: 'status' },
                    { label: 'Levy Paid', key: 'levy_paid' },
                    { label: 'Cert Status', key: 'cert_status' },
                    { label: 'Date', key: 'date' }
                ],
                'accreditation': [
                    { label: 'Ref', key: 'ref' },
                    { label: 'Journalist', key: 'journalist' },
                    { label: 'Category', key: 'category' },
                    { label: 'Status', key: 'status' },
                    { label: 'Card Status', key: 'card_status' },
                    { label: 'Date', key: 'date' }
                ],
                'payments': [
                    { label: 'Pay Ref', key: 'pay_ref' },
                    { label: 'App Ref', key: 'app_ref' },
                    { label: 'Applicant', key: 'applicant' },
                    { label: 'Type', key: 'type' },
                    { label: 'Amount', key: 'amount' },
                    { label: 'Currency', key: 'currency' },
                    { label: 'Method', key: 'method' },
                    { label: 'Status', key: 'status' },
                    { label: 'Date', key: 'date' },
                    { label: 'Verified By', key: 'verified_by' }
                ],
                'annual-levy': [
                    { label: 'Name', key: 'name' },
                    { label: 'Proprietor', key: 'proprietor' },
                    { label: 'Category', key: 'category' },
                    { label: 'Year', key: 'year' },
                    { label: 'Turnover', key: 'turnover' },
                    { label: 'Levy', key: 'levy' },
                    { label: 'Status', key: 'status' },
                    { label: 'Date', key: 'date' }
                ],
                'applicant-accounts': [
                    { label: 'Name', key: 'name' },
                    { label: 'Email', key: 'email' },
                    { label: 'Type', key: 'type' },
                    { label: 'Status', key: 'status' },
                    { label: 'Created', key: 'created' },
                    { label: 'Apps', key: 'apps' }
                ],
                'user-activity': [
                    { label: 'Name', key: 'name' },
                    { label: 'Role', key: 'role' },
                    { label: 'Reviewed', key: 'reviewed' },
                    { label: 'Approved', key: 'approved' },
                    { label: 'Revenue', key: 'revenue' },
                    { label: 'Last Act', key: 'last_act' }
                ],
                'audit-logs': [
                    { label: 'Time', key: 'time' },
                    { label: 'User', key: 'user' },
                    { label: 'Role', key: 'role' },
                    { label: 'Action', key: 'action' },
                    { label: 'Details', key: 'details' },
                    { label: 'IP', key: 'ip' }
                ],
                'printing-collection': [
                    { label: 'Type', key: 'type' },
                    { label: 'Ref', key: 'ref' },
                    { label: 'Region', key: 'region' },
                    { label: 'Printed By', key: 'printed_by' },
                    { label: 'Date', key: 'date' }
                ]
            };

            const fetchConfig = async () => {
                const res = await fetch('{{ route("staff.reports.config") }}');
                config.value = await res.json();
                const hash = window.location.hash.replace('#', '');
                activeTab.value = (hash && config.value.tabs[hash]) ? hash : Object.keys(config.value.tabs)[0];
                reloadData();
            };

            const reloadData = async () => {
                loading.value = true;
                summaryData.value = null;
                tableData.value = [];
                chartsData.value = null;

                const params = new URLSearchParams();
                Object.entries(filters.value).forEach(([k, v]) => { if (v) params.append(k, v); });

                try {
                    const res = await fetch(`{{ url('staff/operational-reports/data') }}/${activeTab.value}?${params.toString()}`);
                    const json = await res.json();
                    summaryData.value = json.summary || null;
                    tableData.value = json.data || [];
                    chartsData.value = json.charts || null;
                    columns.value = columnMap[activeTab.value] || [];
                    
                    if (chartsData.value) {
                        setTimeout(renderCharts, 100);
                    }
                } catch (e) { console.error(e); }
                finally { loading.value = false; }
            };

            const renderCharts = () => {
                Object.entries(chartsData.value).forEach(([key, data]) => {
                    const el = document.querySelector('#chart-' + key);
                    if (!el) return;
                    el.innerHTML = '';
                    new ApexCharts(el, {
                        chart: { type: 'donut', height: 250 },
                        labels: data.map(d => d.label || 'Unknown'),
                        series: data.map(d => d.value),
                        legend: { position: 'bottom' }
                    }).render();
                });
            };

            const switchTab = (tab) => {
                activeTab.value = tab;
                window.location.hash = tab;
                reloadData();
            };

            const resetFilters = () => {
                Object.keys(filters.value).forEach(k => filters.value[k] = '');
                reloadData();
            };

            const formatSummaryKey = (key) => key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            const formatValue = (val, key) => (key.includes('revenue') || key.includes('levy')) ? '$' + Number(val).toLocaleString() : val;
            
            const getSummaryColor = (key) => {
                if (key.includes('approved') || key.includes('paid')) return 'text-success';
                if (key.includes('rejected') || key.includes('failed')) return 'text-danger';
                if (key.includes('review') || key.includes('pending') || key.includes('overdue')) return 'text-warning';
                return 'text-primary';
            };

            const getSummaryIcon = (key) => {
                if (key.includes('apps') || key.includes('application')) return 'ri-file-list-3-line';
                if (key.includes('revenue')) return 'ri-money-dollar-circle-line';
                if (key.includes('printed')) return 'ri-printer-line';
                if (key.includes('payment') || key.includes('paid')) return 'ri-bank-card-line';
                if (key.includes('review')) return 'ri-time-line';
                if (key.includes('rejected')) return 'ri-close-circle-line';
                return 'ri-bar-chart-box-line';
            };

            const getStatusBadge = (status) => {
                const s = status.toLowerCase();
                if (s.includes('approved') || s.includes('paid')) return 'badge-soft-success';
                if (s.includes('rejected') || s.includes('failed')) return 'badge-soft-danger';
                if (s.includes('review') || s.includes('pending')) return 'badge-soft-warning';
                return 'badge-soft-primary';
            };

            const getSLABadge = (sla) => {
                if (sla === 'Overdue') return 'badge-soft-danger';
                if (sla === 'Due Soon') return 'badge-soft-warning';
                return 'badge-soft-success';
            };

            const printReport = () => window.print();

            const exportReport = (format) => {
                const params = new URLSearchParams();
                Object.entries(filters.value).forEach(([k, v]) => { if (v) params.append(k, v); });
                params.append('tab', activeTab.value);
                params.append('format', format);
                window.open(`{{ route('staff.reports.export') }}?${params.toString()}`, '_blank');
            };

            onMounted(fetchConfig);

            return {
                config, activeTab, summaryData, tableData, chartsData, loading, filters, columns,
                switchTab, reloadData, resetFilters, formatSummaryKey, formatValue,
                getSummaryColor, getSummaryIcon, getStatusBadge, getSLABadge, printReport, exportReport
            };
        }
    }).mount('#reportsApp');
</script>
@endpush
@endsection
