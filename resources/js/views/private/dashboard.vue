<template>
<div class="container-fluid py-4" id="dashboard-content">

    <!-- 1. Header (Export Only) - Search is inside the filter card -->
    <div class="row mb-3" v-if="exporting">
        <div class="col-12">
            <h2 class="text-center">Dashboard Comparison Report</h2>
            <p class="text-center text-muted">Generated on: {{ new Date().toLocaleString() }}</p>
        </div>
    </div>

    <!-- 2. Metrics & Filter Row -->
    <div class="row g-3 mb-4">

        <!-- Metric 1: Total Queries -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card text-white bg-primary h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column justify-content-center p-4">
                    <div class="metric-title text-white-50 text-uppercase small fw-bold mb-2">Total Queries</div>

                    <!-- Current Period -->
                    <div class="metric-value fw-bold display-6">
                        <small class="fs-6 fw-normal opacity-75 d-block mb-1">Current Period:</small>
                        {{ dataA.stats.totalQuestions || 0 }}
                        <!-- Arrow Indicator -->
                        <span v-if="dataA.stats.totalQuestions > dataB.stats.totalQuestions" class="fs-5 ms-2 text-success-light" >▲</span>
                        <span v-else-if="dataA.stats.totalQuestions < dataB.stats.totalQuestions" class="fs-5 ms-2 text-warning-light" >▼</span>
                    </div>

                    <!-- Comparison Period -->
                    <div v-if="isCompareMode" class="metric-sub-value mt-3 pt-3 border-top border-light border-opacity-25">
                        <small class="fs-6 fw-normal opacity-75 d-block mb-1">Comparison Period:</small>
                        <span class="fs-4 fw-bold">{{ dataB.stats.totalQuestions || 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 2: Total Success -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card text-white bg-info h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column justify-content-center p-4">
                    <div class="metric-title text-white-50 text-uppercase small fw-bold mb-2">Total Success</div>

                    <div class="metric-value fw-bold display-6">
                        <small class="fs-6 fw-normal opacity-75 d-block mb-1">Current Period:</small>
                        {{ dataA.stats.totalSuccess || 0 }}
                        <span v-if="dataA.stats.totalSuccess > dataB.stats.totalSuccess" class="fs-5 ms-2 text-success-light" >▲</span>
                        <span v-else-if="dataA.stats.totalSuccess < dataB.stats.totalSuccess" class="fs-5 ms-2 text-warning-light" >▼</span>
                    </div>

                    <div v-if="isCompareMode" class="metric-sub-value mt-3 pt-3 border-top border-light border-opacity-25">
                        <small class="fs-6 fw-normal opacity-75 d-block mb-1">Comparison Period:</small>
                        <span class="fs-4 fw-bold">{{ dataB.stats.totalSuccess || 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 3: Total Failed -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card text-white bg-purple h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column justify-content-center p-4">
                    <div class="metric-title text-white-50 text-uppercase small fw-bold mb-2">Total Failed</div>

                    <div class="metric-value fw-bold display-6">
                        <small class="fs-6 fw-normal opacity-75 d-block mb-1">Current Period:</small>
                        {{ dataA.stats.totalFail || 0 }}
                        <span v-if="dataA.stats.totalFail > dataB.stats.totalFail" class="fs-5 ms-2 text-success-light" >▲</span>
                        <span v-else-if="dataA.stats.totalFail < dataB.stats.totalFail" class="fs-5 ms-2 text-warning-light" >▼</span>
                    </div>

                    <div v-if="isCompareMode" class="metric-sub-value mt-3 pt-3 border-top border-light border-opacity-25">
                        <small class="fs-6 fw-normal opacity-75 d-block mb-1">Comparison Period:</small>
                        <span class="fs-4 fw-bold">{{ dataB.stats.totalFail || 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Control Panel (Filter) -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card bg-light h-100 shadow-sm border-0" data-html2canvas-ignore="true">
                <div class="card-body p-3 d-flex flex-column">

                    <!-- Filter A -->
                    <div class="mb-2 bg-white p-2 rounded border">
                        <label class="small text-muted fw-bold mb-1">Current Period ({{ period1Label }})</label>
                        <select v-model="filterA.type" class="form-select form-select-sm mb-1">
                            <option value="all-time">All Time</option>
                            <option value="daily">Daily (Today)</option>
                            <option value="weekly">Weekly (This Week)</option>
                            <option value="monthly">Monthly (This Month)</option>
                            <option value="yearly">Yearly (This Year)</option>
                            <option value="custom-range">Custom Range</option>
                        </select>
                    <div v-if="filterA.type === 'custom-range'" class="mt-1">

                        <!-- 💻 电脑端 (LG以上显示): 保持原汁原味的简单设计 -->
                        <div class="d-none d-lg-flex gap-2">
                            <input type="date" v-model="filterA.start" class="form-control form-control-sm" title="Start Date">
                            <input type="date" v-model="filterA.end" class="form-control form-control-sm" title="End Date">
                        </div>

                        <!-- 📱 手机/平板端 (LG以下显示): 垂直堆叠 + 明确标签 -->
                        <div class="d-flex d-lg-none flex-column gap-2">
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary justify-content-center" style="min-width: 60px;">From</span>
                                <input type="date" v-model="filterA.start" class="form-control mobile-date-input" required>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary justify-content-center" style="min-width: 60px;">To</span>
                                <input type="date" v-model="filterA.end" class="form-control mobile-date-input" required>
                            </div>
                        </div>

                    </div>
                    </div>

                    <!-- Toggle Switch -->
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="compareToggle" v-model="isCompareMode">
                        <label class="form-check-label small fw-bold" for="compareToggle">Enable Comparison</label>
                    </div>

                    <!-- Filter B -->
                    <div v-if="isCompareMode" class="mb-3 bg-white p-2 rounded border animate-slide-down">
                        <label class="small text-muted fw-bold mb-1">Comparison Period ({{ period2Label }})</label>
                        <select v-model="filterB.type" class="form-select form-select-sm mb-1">
                            <option value="all-time">All Time</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="custom-range">Custom Range</option>
                        </select>
                    <div v-if="filterB.type === 'custom-range'" class="mt-1">

                        <!-- 💻 电脑端 -->
                        <div class="d-none d-lg-flex gap-2">
                            <input type="date" v-model="filterB.start" class="form-control form-control-sm" title="Start Date">
                            <input type="date" v-model="filterB.end" class="form-control form-control-sm" title="End Date">
                        </div>

                        <!-- 📱 手机端 -->
                        <div class="d-flex d-lg-none flex-column gap-2">
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary justify-content-center" style="min-width: 60px;">From</span>
                                <input type="date" v-model="filterB.start" class="form-control mobile-date-input" required>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary justify-content-center" style="min-width: 60px;">To</span>
                                <input type="date" v-model="filterB.end" class="form-control mobile-date-input" required>
                            </div>
                        </div>

                    </div>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-auto d-grid gap-2 d-md-flex">
                        <button @click="handleSearch" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-search me-1"></i> Search
                        </button>
                        <div class="btn-group flex-grow-1">
                            <button type="button" class="btn btn-success btn-sm dropdown-toggle w-100" data-bs-toggle="dropdown" :disabled="loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                                <i v-else class="bi bi-download me-1"></i> Export
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="#" @click.prevent="exportToExcel"><i class="bi bi-file-excel text-success me-2"></i>Excel Data</a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="exportToPDF"><i class="bi bi-file-pdf text-danger me-2"></i>PDF Report</a></li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div :class="isCompareMode ? 'col-12 col-md-6' : 'col-12'">
            <div class="card p-2 h-100">
                <h6 class="text-center fw-bold p-2">Intent Queries <span v-if="isCompareMode">(Current Period: {{ period1Label }})</span></h6>
                <div style="height: auto;">
                    <BarChart :chart-data="chartDataA.intents" :options="barChartOptions" />
                </div>
                <p v-if="!chartDataA.intents.labels.length" class="text-center mt-5 text-muted">No data</p>
            </div>
        </div>

        <div class="col-12 col-md-6" v-if="isCompareMode">
            <div class="card p-2 h-100 border-primary">
                <h6 class="text-center fw-bold p-2 text-primary">Intent Queries (Comparison Period: {{ period2Label }})</h6>
                <div style="height: auto;">
                    <BarChart :chart-data="chartDataB.intents" :options="barChartOptions" />
                </div>
                <p v-if="!chartDataB.intents.labels.length" class="text-center mt-5 text-muted">No data</p>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div :class="isCompareMode ? 'col-12 col-md-6' : 'col-12'">
            <div class="card p-2 h-100">
                <h6 class="text-center fw-bold p-2">Department Trends <span v-if="isCompareMode">(Current Period: {{ period1Label }})</span></h6>
                <div style="height: auto;">
                    <LineChart :chart-data="chartDataA.trends" :options="lineChartOptions" />
                </div>
                <p v-if="!chartDataA.trends.datasets.length" class="text-center mt-5 text-muted">No trend data</p>
            </div>
        </div>

        <div class="col-12 col-md-6" v-if="isCompareMode">
            <div class="card p-2 h-100 border-primary">
                <h6 class="text-center fw-bold p-2 text-primary">Department Trends (Comparison Period: {{ period2Label }})</h6>
                <div style="height: auto;">
                    <LineChart :chart-data="chartDataB.trends" :options="lineChartOptions" />
                </div>
                <p v-if="!chartDataB.trends.datasets.length" class="text-center mt-5 text-muted">No trend data</p>
            </div>
        </div>
    </div>

    <!-- 4. Top 10 FAQs (Dual View: Table for Desktop, List for Mobile) -->
    <div class="row g-4 mt-4">
        <!-- Current Period -->
        <div :class="isCompareMode ? 'col-12 col-xl-6' : 'col-12'">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Top 10 FAQs <span v-if="isCompareMode" class="text-muted fw-normal small ms-1">(Current Period)</span></h6>
                </div>
                <div class="card-body p-0">

                    <!-- 💻 Desktop Table -->
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="px-4">#</th>
                                    <th>Question</th>
                                    <th class="text-end px-4">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in dataA.faqs.Faq" :key="index">
                                    <td class="px-4 fw-bold text-secondary">{{ index + 1 }}</td>
                                    <td class="text-truncate" style="max-width: 300px;">{{ item.question }}</td>
                                    <td class="text-end px-4 fw-bold text-dark">{{ item.total }}</td>
                                </tr>
                                <tr v-if="!dataA.faqs.Faq?.length">
                                    <td colspan="3" class="text-center py-4 text-muted small">No data available</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 📱 Mobile List -->
                    <ul class="d-md-none list-group list-group-flush">
                        <li v-for="(item, index) in dataA.faqs.Faq" :key="index" class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="badge bg-light text-dark border me-2">#{{ index + 1 }}</span>
                                <span class="badge bg-primary rounded-pill">{{ item.total }}</span>
                            </div>
                            <div class="mt-2 fw-medium small">{{ item.question }}</div>
                        </li>
                        <li v-if="!dataA.faqs.Faq?.length" class="list-group-item text-center py-4 text-muted small">
                            No data available
                        </li>
                    </ul>

                </div>
            </div>
        </div>

        <!-- Comparison Period -->
        <div class="col-12 col-xl-6" v-if="isCompareMode">
            <div class="card h-100 shadow-sm border-start border-primary border-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-primary">Top 10 FAQs <span class="text-muted fw-normal small ms-1">(Comparison Period)</span></h6>
                </div>
                <div class="card-body p-0">

                    <!-- 💻 Desktop Table -->
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-primary bg-opacity-10 text-primary small text-uppercase">
                                <tr>
                                    <th class="px-4">#</th>
                                    <th>Question</th>
                                    <th class="text-end px-4">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in dataB.faqs.Faq" :key="index">
                                    <td class="px-4 fw-bold text-secondary">{{ index + 1 }}</td>
                                    <td class="text-truncate" style="max-width: 300px;">{{ item.question }}</td>
                                    <td class="text-end px-4 fw-bold text-dark">{{ item.total }}</td>
                                </tr>
                                <tr v-if="!dataB.faqs.Faq?.length">
                                    <td colspan="3" class="text-center py-4 text-muted small">No data available</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 📱 Mobile List -->
                    <ul class="d-md-none list-group list-group-flush">
                        <li v-for="(item, index) in dataB.faqs.Faq" :key="index" class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="badge bg-light text-dark border me-2">#{{ index + 1 }}</span>
                                <span class="badge bg-primary rounded-pill">{{ item.total }}</span>
                            </div>
                            <div class="mt-2 fw-medium small">{{ item.question }}</div>
                        </li>
                        <li v-if="!dataB.faqs.Faq?.length" class="list-group-item text-center py-4 text-muted small">
                            No data available
                        </li>
                    </ul>

                </div>
            </div>
        </div>
    </div>

    <div class="position-fixed bottom-0 end-0 p-4" style="z-index: 1050;" v-if="showFab" data-html2canvas-ignore="true">
        <button @click="aiSummary ? scrollToBottom() : triggerAnalysis()"
                class="btn rounded-circle shadow-lg p-3 d-flex align-items-center justify-content-center"
                :class="aiSummary ? 'btn-secondary' : 'btn-primary'"
                style="width: 60px; height: 60px;"
                :title="aiSummary ? 'View Analysis' : 'Go to Generate'">
            <i class="bi" :class="aiSummary ? 'bi-file-text fs-4' : 'bi-stars fs-4'"></i>
        </button>
    </div>

    <div class="row mt-3" id="ai-result-section" ref="aiSectionRef">
        <div class="col-12">

            <div v-if="!aiSummary && !analyzing" class="text-center">
                <button @click="generateAnalysis" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-stars"></i> Generate AI Comparison Analysis
                </button>
            </div>

            <div v-else-if="analyzing" class="text-center p-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Gemini is analyzing data...</p>
            </div>

            <div v-else class="card border-info shadow-sm">
                <div class="card-header bg-white text-info d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-robot me-2"></i>AI Executive Summary</span>
                    <button @click="generateAnalysis" class="btn btn-sm btn-link text-muted" title="Regenerate">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                <div class="card-body bg-light-subtle">
                    <p class="card-text" style="white-space: pre-line;">{{ aiSummary }}</p>
                    <div class="text-end">
                        <small class="text-muted" style="font-size: 0.8rem;">Generated on {{ new Date().toLocaleString() }}</small>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
</template>

<script>
import { ref, reactive, onMounted, onUnmounted, computed, watch } from 'vue';
import axios from 'axios';
import { BarChart, LineChart } from 'vue-chart-3';
import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';

Chart.register(...registerables, ChartDataLabels);

export default {
    components: { BarChart, LineChart },
    setup() {
        const token = localStorage.getItem('sanctum_token');
        const loading = ref(false);
        const exporting = ref(false);
        const isCompareMode = ref(false);
        const analyzing = ref(false);
        const aiSummary = ref("");
        const showFab = ref(false);
        const aiSectionRef = ref(null);
        let observer = null;

        const filterA = reactive({ type: 'all-time', start: null, end: null });
        const filterB = reactive({ type: 'all-time', start: null, end: null });

        const dataA = reactive({ faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });
        const dataB = reactive({ faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });

        const getRandomColor = () => '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
        const getDiff = (a, b) => (a || 0) - (b || 0);

        const period1Label = computed(() => {
            if (filterA.type === 'custom-range' && filterA.start && filterA.end) return `${filterA.start} ~ ${filterA.end}`;
            return filterA.type.charAt(0).toUpperCase() + filterA.type.slice(1);
        });

        const period2Label = computed(() => {
            if (filterB.type === 'custom-range' && filterB.start && filterB.end) return `${filterB.start} ~ ${filterB.end}`;
            return filterB.type.charAt(0).toUpperCase() + filterB.type.slice(1);
        });

        const formatChartData = (sourceData, sourceTrend) => {
            const intentRaw = sourceData.Intent || [];
            return {
                intents: {
                    labels: intentRaw.map(i => i.intent_name),
                    datasets: [{
                        label: 'Queries',
                        data: intentRaw.map(i => i.total),
                        backgroundColor: intentRaw.map(() => getRandomColor()),
                        barPercentage: 0.6,
                        borderRadius: 4
                    }]
                },
                trends: sourceTrend
            };
        };

        const chartDataA = computed(() => formatChartData(dataA.faqs, dataA.trend));
        const chartDataB = computed(() => formatChartData(dataB.faqs, dataB.trend));

        const fetchDataInternal = async (filterType, startDate, endDate) => {
            let q = `?filter=${filterType}`;
            if (filterType === 'custom-range' && startDate && endDate) q += `&startDate=${startDate}&endDate=${endDate}`;
            try {
                const [resFaqs, resTrend, resStats] = await Promise.all([
                    axios.get(`/api/top10Faqs${q}`, { headers: { Authorization: `Bearer ${token}` } }),
                    axios.get(`/api/department-trend${q}`, { headers: { Authorization: `Bearer ${token}` } }),
                    axios.get(`/api/getDashboardMetrics${q}`, { headers: { Authorization: `Bearer ${token}` } }).catch(() => ({ data: {} }))
                ]);

                const ds = resTrend.data.datasets ? resTrend.data.datasets.map(d => ({
                    ...d, borderColor: getRandomColor(), backgroundColor: 'transparent', tension: 0.3, borderWidth: 2, pointRadius: 3
                })) : [];

                return {
                    faqs: resFaqs.data,
                    stats: {
                        totalQuestions: resStats.data.totalQuestions || 0,
                        totalSuccess: resStats.data.totalSuccess || 0,
                        totalFail: resStats.data.totalFail || 0
                    },
                    trend: { labels: resTrend.data.labels || [], datasets: ds }
                };
            } catch (e) { return { faqs: {}, stats: {}, trend: { labels: [], datasets: [] } }; }
        };

        const handleSearch = async () => {
            if (filterA.type === 'custom-range' && (!filterA.start || !filterA.end)) return;
            aiSummary.value = ""; loading.value = true;
            Object.assign(dataA, await fetchDataInternal(filterA.type, filterA.start, filterA.end));

            if (isCompareMode.value && (filterB.type !== 'custom-range' || (filterB.start && filterB.end))) {
                Object.assign(dataB, await fetchDataInternal(filterB.type, filterB.start, filterB.end));
            } else {
                Object.assign(dataB, { faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });
            }
            loading.value = false;
        };

        watch(() => filterA.type, (v) => { if(v !== 'custom-range') handleSearch(); });
        watch(() => filterB.type, (v) => { if(isCompareMode.value && v !== 'custom-range') handleSearch(); });
        watch(isCompareMode, (v) => {
            aiSummary.value = "";
            if (v) {
                filterA.type = 'custom-range'; filterB.type = 'custom-range';
                filterA.start = null; filterA.end = null; filterB.start = null; filterB.end = null;
                Object.assign(dataA, { faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });
                Object.assign(dataB, { faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });
            } else {
                filterA.type = 'all-time';
            }
        });

        const exportToExcel = () => {
            const wb = XLSX.utils.book_new();

            // 1. Summary
            const summaryRows = [
                ["Report Generated", new Date().toLocaleString() ? "Formular = Current - Comparison = Change": ""],
                ["Mode", isCompareMode.value ? "Comparison" : "Single Period"],
                // 🔥 修改：使用 period1Label.value
                ["Current Period", period1Label.value],
                // 🔥 修改：使用 period2Label.value
                isCompareMode.value ? ["Comparison Period", period2Label.value] : [],
                [],
                ["Metric", "Current Period", isCompareMode.value ? "Comparison Period" : "", isCompareMode.value ? "Change" : ""],
                ["Total Queries", dataA.stats.totalQuestions, isCompareMode.value ? dataB.stats.totalQuestions : "", isCompareMode.value ? dataA.stats.totalQuestions - dataB.stats.totalQuestions : ""],
                ["Success", dataA.stats.totalSuccess, isCompareMode.value ? dataB.stats.totalSuccess : "", isCompareMode.value ? dataA.stats.totalSuccess - dataB.stats.totalSuccess : ""],
                ["Failed", dataA.stats.totalFail, isCompareMode.value ? dataB.stats.totalFail : "", isCompareMode.value ? dataA.stats.totalFail - dataB.stats.totalFail : ""]
            ];
            const wsSummary = XLSX.utils.aoa_to_sheet(summaryRows);
            wsSummary['!cols'] = [{ wch: 20 }, { wch: 15 }, { wch: 15 }];
            XLSX.utils.book_append_sheet(wb, wsSummary, "Summary");

            // 2. Intents (Merged Side-by-Side)
            const allIntents = new Set([
                ...(dataA.faqs.Intent || []).map(i => i.intent_name),
                ...(isCompareMode.value ? (dataB.faqs.Intent || []).map(i => i.intent_name) : [])
            ]);

            const intentRows = Array.from(allIntents).map(name => {
                const valA = (dataA.faqs.Intent || []).find(i => i.intent_name === name)?.total || 0;
                const valB = (dataB.faqs.Intent || []).find(i => i.intent_name === name)?.total || 0;
                return {
                    "Intent": name,
                    "Current Period": valA,
                    ...(isCompareMode.value && { "Comparison Period": valB, "Change": valA - valB })
                };
            });
            XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(intentRows), "Intents");

            const allDepts = new Set([
                ...(dataA.faqs.Department || []).map(d => d.name),
                ...(isCompareMode.value ? (dataB.faqs.Department || []).map(d => d.name) : [])
            ]);

            const deptRows = Array.from(allDepts).map(name => {
                const valA = (dataA.faqs.Department || []).find(d => d.name === name)?.total || 0;
                const valB = (dataB.faqs.Department || []).find(d => d.name === name)?.total || 0;
                return {
                    "Department": name,
                    "Current Period": valA,
                    ...(isCompareMode.value && { "Comparison Period": valB, "Change": valA - valB })
                };
            });

            XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(deptRows), "Departments");
            // 3. FAQs (Side by Side)
            const faqRows = [];
            const maxLen = Math.max(dataA.faqs.Faq?.length || 0, dataB.faqs.Faq?.length || 0);
            for(let i=0; i<maxLen; i++) {
                const row = {};
                if(dataA.faqs.Faq?.[i]) {
                    row["Current Period Rank"] = i+1;
                    row["Current Period Question"] = dataA.faqs.Faq[i].question;
                    row["Current Period Count"] = dataA.faqs.Faq[i].total;
                }
                if(isCompareMode.value && dataB.faqs.Faq?.[i]) {
                    row["|"] = "|"; // Separator
                    row["Comparison Period Rank"] = i+1;
                    row["Comparison Period Question"] = dataB.faqs.Faq[i].question;
                    row["Comparison Period Count"] = dataB.faqs.Faq[i].total;
                    row["Change"] = dataA.faqs.Faq[i].total - dataB.faqs.Faq[i].total;
                }
                faqRows.push(row);
            }
            const wsFaq = XLSX.utils.json_to_sheet(faqRows);
            wsFaq['!cols'] = [{ wch: 5 }, { wch: 40 }, { wch: 10 }, { wch: 2 }, { wch: 5 }, { wch: 40 }, { wch: 10 }];
            XLSX.utils.book_append_sheet(wb, wsFaq, "FAQs");

            XLSX.writeFile(wb, `Report_${new Date().toISOString().slice(0,10)}.xlsx`);
        };

        const exportToPDF = async () => {
            exporting.value = true;
            loading.value = true;
            if (!aiSummary.value && confirm("Generate AI Analysis?")) await generateAnalysis();
            await new Promise(r => setTimeout(r, 500));

            const element = document.getElementById('dashboard-content');
            try {
                const canvas = await html2canvas(element, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('p', 'mm', 'a4');
                const pdfW = pdf.internal.pageSize.getWidth();
                const pdfH = pdf.internal.pageSize.getHeight();
                const imgH = (canvas.height * pdfW) / canvas.width;

                let heightLeft = imgH;
                let position = 0;
                pdf.addImage(imgData, 'PNG', 0, position, pdfW, imgH);
                heightLeft -= pdfH;
                while (heightLeft >= 0) {
                    position = heightLeft - imgH;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, pdfW, imgH);
                    heightLeft -= pdfH;
                }
                pdf.save(`Report_${new Date().toISOString().slice(0,10)}.pdf`);
            } catch(e) { console.error(e); }
            finally { loading.value = false; exporting.value = false; }
        };

        const generateAnalysis = async () => {
            analyzing.value = true;
            try {
                const payload = {
                    mode: isCompareMode.value ? 'comparison' : 'single',
                    period1: { filter: period1Label.value, stats: dataA.stats, topIntent: dataA.faqs.Intent?.[0]?.intent_name },
                    period2: isCompareMode.value ? { range: period2Label.value, stats: dataB.stats, topIntent: dataB.faqs.Intent?.[0]?.intent_name } : null
                };
                const res = await axios.post('/api/generate-summary', { stats: payload }, { headers: { Authorization: `Bearer ${token}` } });
                aiSummary.value = res.data.summary;
            } catch(e) { alert("AI Error"); } finally { analyzing.value = false; }
        };

        const triggerAnalysis = () => {
            scrollToBottom();
            generateAnalysis();
        };

        const scrollToBottom = () => aiSectionRef.value?.scrollIntoView({ behavior: 'smooth' });

        const setupObserver = () => {
            observer = new IntersectionObserver((e) => { showFab.value = !e[0].isIntersecting; }, { threshold: 0.1 });
            if (aiSectionRef.value) observer.observe(aiSectionRef.value);
        };

        const barChartOptions = ref({ responsive: true, maintainAspectRatio: false, plugins: { legend: {display: false}, datalabels: {color: '#fff', font: {weight: 'bold'}} }, scales: { y: {beginAtZero: true, grid: {display: false} }, x: {grid: {display: false}} } });
        const lineChartOptions = ref({ responsive: true, maintainAspectRatio: false, plugins: { legend: {position: 'top', labels: {boxWidth: 10, usePointStyle: true}} }, scales: { y: {beginAtZero: true} } });

        onMounted(() => { handleSearch(); setupObserver(); });
        onUnmounted(() => { if (observer) observer.disconnect(); });

        return {
            loading, exporting, isCompareMode, analyzing, aiSummary, aiSectionRef, showFab,
            filterA, filterB, dataA, dataB, chartDataA, chartDataB,
            barChartOptions, lineChartOptions, handleSearch, exportToExcel, exportToPDF,
            generateAnalysis, triggerAnalysis, scrollToBottom, getDiff, period1Label, period2Label
        };
    }
}
</script>

<style scoped>
/* 颜色变量 */
.bg-purple { background-color: #6f42c1 !important; }
.bg-gradient-primary { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); }
.text-success-light { color: #d1e7dd !important; }
.text-warning-light { color: #fff3cd !important; }

/* 手机端图表高度微调 */
@media (max-width: 768px) {
    .chart-wrapper {
        min-height: 250px;
        height: 280px;
    }
}

/* 悬浮按钮样式 */
.fab-btn {
    width: 56px;
    height: 56px;
    font-size: 1.5rem;
    transition: transform 0.2s;
}
.fab-btn:active { transform: scale(0.9); }

/* AI 文本排版 */
.ai-text {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #2c3e50;
}

/* 下滑动画 */
.animate-slide-down {
    animation: slideDown 0.3s ease-out;
}

/* 📱 修复手机端 Date Picker 空白问题 */
.mobile-date-input {
    color: #212529;
    background-color: #fff;
    min-height: 40px; /* 手机上稍微高一点，好点 */
    font-size: 1rem;  /* 手机上字体大一点防止缩放 */
}

/* 利用 :invalid 伪类在手机上显示 "Select Date" 提示 */
.mobile-date-input:invalid::-webkit-datetime-edit {
    color: transparent;
}
.mobile-date-input:invalid::before {
    content: "Select Date";
    color: #999;
    margin-right: 0.5rem;
    position: absolute;
}

/* 修复部分 Android 浏览器日期图标太大的问题 */
input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: 0.6;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
