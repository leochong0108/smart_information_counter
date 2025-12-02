<template>
<div class="container-fluid mt-4" id="dashboard-content">

    <div class="row mb-3" v-if="exporting">
        <div class="col-12">
            <h2 class="text-center">Dashboard Comparison Report</h2>
            <p class="text-center text-muted">Generated on: {{ new Date().toLocaleString() }}</p>
        </div>
    </div>

    <div class="row">

        <div class="col-12 col-md-3 mb-1">
            <div class="metric-card card text-white bg-primary h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="metric-title">Total Queries</div>
                    <div class="metric-value font-weight-bold">
                        <h6 v-if="isCompareMode">Period 1:</h6> {{ dataA.stats.totalQuestions || 0 }}
                        <span v-if="dataA.stats.totalQuestions > dataB.stats.totalQuestions" class="text-success" style="font-size: 1rem;">▲</span>
                        <span v-else-if="dataA.stats.totalQuestions < dataB.stats.totalQuestions" class="text-warning" style="font-size: 1rem;">▼</span>
                    </div>
                    <div v-if="isCompareMode" class="metric-value font-weight-bold small opacity-75 mt-1 border-top pt-1">
                        <h6>Period 2:</h6>  {{ dataB.stats.totalQuestions || 0 }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3 mb-1">
            <div class="metric-card card text-white bg-info h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="metric-title">Total Success</div>
                    <div class="metric-value font-weight-bold">
                        <h6 v-if="isCompareMode">Period 1:</h6> {{ dataA.stats.totalSuccess || 0 }}
                        <span v-if="dataA.stats.totalSuccess > dataB.stats.totalSuccess" class="text-success" style="font-size: 1rem;">▲</span>
                        <span v-else-if="dataA.stats.totalSuccess < dataB.stats.totalSuccess" class="text-warning" style="font-size: 1rem;">▼</span>
                    </div>
                    <div v-if="isCompareMode" class="metric-value font-weight-bold small opacity-75 mt-1 border-top pt-1">
                        <h6>Period 2:</h6> {{ dataB.stats.totalSuccess || 0 }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3 mb-1">
            <div class="metric-card card text-white bg-purple h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="metric-title">Total Failed</div>
                    <div class="metric-value font-weight-bold">
                        <h6 v-if="isCompareMode">Period 1:</h6> {{ dataA.stats.totalFail || 0 }}
                        <span v-if="dataA.stats.totalFail > dataB.stats.totalFail" class="text-success" style="font-size: 1rem;">▲</span>
                        <span v-else-if="dataA.stats.totalFail < dataB.stats.totalFail" class="text-warning" style="font-size: 1rem;">▼</span>
                    </div>
                    <div v-if="isCompareMode" class="metric-value font-weight-bold small opacity-75 mt-1 border-top pt-1">
                        <h6>Period 2:</h6> {{ dataB.stats.totalFail || 0 }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3 mb-1">
            <div class="metric-card card bg-light p-2 h-100"  data-html2canvas-ignore="true">

                <div class="d-flex flex-column mb-2 p-1 border rounded bg-white">
                    <small v-if="isCompareMode" class="text-muted mb-1" style="font-size: 0.75rem;">Period 1 (Newest):</small>

                    <select v-model="filterA.type" class="form-select form-select-sm mb-1">
                        <option value="all-time">All Time</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                        <option value="custom-range">Custom Range</option>
                    </select>

                    <div v-if="filterA.type === 'custom-range'" class="d-flex gap-1">
                        <input type="date" v-model="filterA.start" class="form-control form-control-sm">
                        <input type="date" v-model="filterA.end" class="form-control form-control-sm">
                    </div>
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="compareToggle" v-model="isCompareMode">
                    <label class="form-check-label small fw-bold" for="compareToggle">Compare Period</label>
                </div>

                <div v-if="isCompareMode" class="d-flex flex-column mb-2 p-1 border rounded bg-white">
                    <small class="text-muted mb-1" style="font-size: 0.75rem;">Period 2:</small>

                    <select v-model="filterB.type" class="form-select form-select-sm mb-1">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                        <option value="custom-range">Custom Range</option>
                    </select>

                    <div v-if="filterB.type === 'custom-range'" class="d-flex gap-1">
                        <input type="date" v-model="filterB.start" class="form-control form-control-sm">
                        <input type="date" v-model="filterB.end" class="form-control form-control-sm">
                    </div>
                </div>

                <div class="mt-auto">
                    <div class="row g-2">
                        <div class="col-6">
                            <button @click="handleSearch" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="bi bi-search me-2"></i> Search
                            </button>
                        </div>

                        <div class="col-6">
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-success w-100 dropdown-toggle d-flex align-items-center justify-content-center" data-bs-toggle="dropdown" :disabled="loading">
                                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                    <i v-else class="bi bi-file-earmark-arrow-down me-2"></i>
                                    Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end w-100">
                                    <li><h6 class="dropdown-header">Choose Format</h6></li>
                                    <li>
                                        <a class="dropdown-item" href="#" @click.prevent="exportToExcel">
                                            <i class="bi bi-file-excel text-success me-2"></i>Excel (Data)
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" @click.prevent="exportToPDF">
                                            <i class="bi bi-file-pdf text-danger me-2"></i>PDF (Report)
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div :class="isCompareMode ? 'col-12 col-md-6' : 'col-12'">
            <div class="card p-2 h-100">
                <h6 class="text-center fw-bold p-2">Intent Queries <span v-if="isCompareMode">(Period 1: {{ period1Label }})</span></h6>
                <div style="height: auto;">
                    <BarChart :chart-data="chartDataA.intents" :options="barChartOptions" />
                </div>
                <p v-if="!chartDataA.intents.labels.length" class="text-center mt-5 text-muted">No data</p>
            </div>
        </div>

        <div class="col-12 col-md-6" v-if="isCompareMode">
            <div class="card p-2 h-100 border-primary">
                <h6 class="text-center fw-bold p-2 text-primary">Intent Queries (Period 2: {{ period2Label }})</h6>
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
                <h6 class="text-center fw-bold p-2">Department Trends <span v-if="isCompareMode">(Period 1: {{ period1Label }})</span></h6>
                <div style="height: auto;">
                    <LineChart :chart-data="chartDataA.trends" :options="lineChartOptions" />
                </div>
                <p v-if="!chartDataA.trends.datasets.length" class="text-center mt-5 text-muted">No trend data</p>
            </div>
        </div>

        <div class="col-12 col-md-6" v-if="isCompareMode">
            <div class="card p-2 h-100 border-primary">
                <h6 class="text-center fw-bold p-2 text-primary">Department Trends (Period 2: {{ period2Label }})</h6>
                <div style="height: auto;">
                    <LineChart :chart-data="chartDataB.trends" :options="lineChartOptions" />
                </div>
                <p v-if="!chartDataB.trends.datasets.length" class="text-center mt-5 text-muted">No trend data</p>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div :class="isCompareMode ? 'col-12 col-md-6' : 'col-12'">
            <div class="card p-3 h-100">
                <h5 class="card-title">Top 10 FAQs <span v-if="isCompareMode">(Period 1: {{ period1Label }})</span></h5>
                <table class="table table-hover table-sm mt-2">
                    <thead class="table-light">
                        <tr><th>#</th><th>Question</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in dataA.faqs.Faq" :key="index">
                            <td>{{ index + 1 }}</td>
                            <td class="text-truncate" style="max-width: 250px;">{{ item.question }}</td>
                            <td>{{ item.total }}</td>
                        </tr>
                        <tr v-if="!dataA.faqs.Faq?.length"><td colspan="3" class="text-center text-muted">No data</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-12 col-md-6" v-if="isCompareMode">
            <div class="card p-3 h-100 border-primary">
                <h5 class="card-title text-primary">Top 10 FAQs (Period 2: {{ period2Label }})</h5>
                <table class="table table-hover table-sm mt-2">
                    <thead class="table-primary">
                        <tr><th>#</th><th>Question</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in dataB.faqs.Faq" :key="index">
                            <td>{{ index + 1 }}</td>
                            <td class="text-truncate" style="max-width: 250px;">{{ item.question }}</td>
                            <td>{{ item.total }}</td>
                        </tr>
                        <tr v-if="!dataB.faqs.Faq?.length"><td colspan="3" class="text-center text-muted">No data</td></tr>
                    </tbody>
                </table>
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
                        <small class="text-muted" style="font-size: 0.7rem;">Generated on {{ new Date().toLocaleString() }}</small>
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
import { BarChart, LineChart } from 'vue-chart-3'; // 移除了 PieChart
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
        const showFab = ref(false); // 控制悬浮按钮显示
        const aiSectionRef = ref(null); // 绑定 DOM 元素
        let observer = null; // 观察器实例

        // --- Filters ---
        const filterA = reactive({ type: 'all-time', start: null, end: null });
        // Period 2 只需要日期，不需要 type
        const filterB = reactive({ type: 'all-time', start: null, end: null });

        // --- Data Containers ---
        // 移除了 department (Pie Chart) 的特定处理，trend 已经包含了 department 信息
        const dataA = reactive({ faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });
        const dataB = reactive({ faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });

        const getRandomColor = () => '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
        const getDiff = (a, b) => (a || 0) - (b || 0);

        // --- Format Data for Charts ---
        const formatChartData = (sourceData, sourceTrend) => {
            // Intent Data (Bar)
            const intentRaw = sourceData.Intent || [];
            const intentLabels = intentRaw.map(i => i.intent_name);
            const intentValues = intentRaw.map(i => i.total);
            const intentColors = intentValues.map(() => getRandomColor());

            return {
                intents: {
                    labels: intentLabels,
                    datasets: [{
                        label: 'Queries',
                        data: intentValues,
                        backgroundColor: intentColors,
                        barPercentage: 0.6
                    }]
                },
                trends: sourceTrend // Trend data is already formatted
            };
        };

        const chartDataA = computed(() => formatChartData(dataA.faqs, dataA.trend));
        const chartDataB = computed(() => formatChartData(dataB.faqs, dataB.trend));

        // 🔥 新增：动态计算显示的日期标签
        const period1Label = computed(() => {
            if (filterA.type === 'custom-range' && filterA.start && filterA.end) {
                return `${filterA.start} to ${filterA.end}`;
            }
            // 如果是 presets (weekly/daily)，首字母大写显示
            return `${filterA.type.charAt(0).toUpperCase() + filterA.type.slice(1)}`;
        });

        const period2Label = computed(() => {
            if (filterB.type === 'custom-range' && filterB.start && filterB.end) {
                return `${filterB.start} to ${filterB.end}`;
            }
            // 显示类型名称 (首字母大写)
            return `${filterB.type.charAt(0).toUpperCase() + filterB.type.slice(1)}`;
        });

        // --- Core API Fetcher ---
        const fetchDataInternal = async (filterType, startDate, endDate) => {
            let queryParams = `?filter=${filterType}`;
            if (filterType === 'custom-range' && startDate && endDate) {
                queryParams += `&startDate=${startDate}&endDate=${endDate}`;
            }

            try {
                const [resFaqs, resTrend, resStats] = await Promise.all([
                    axios.get(`/api/top10Faqs${queryParams}`, { headers: { Authorization: `Bearer ${token}` } }),
                    axios.get(`/api/department-trend${queryParams}`, { headers: { Authorization: `Bearer ${token}` } }),
                    axios.get(`/api/getDashboardMetrics${queryParams}`, { headers: { Authorization: `Bearer ${token}` } }).catch(() => ({ data: {} }))
                ]);

                const datasetsWithColor = resTrend.data.datasets ? resTrend.data.datasets.map(ds => ({
                    ...ds,
                    borderColor: getRandomColor(),
                    backgroundColor: 'transparent',
                    tension: 0.3
                })) : [];

                return {
                    faqs: resFaqs.data,
                    // 🔥 修改：直接读取 API 返回值，若无则为 0，不再前端计算
                    stats: {
                        totalQuestions: resStats.data.totalQuestions || 0,
                        totalSuccess: resStats.data.totalSuccess || 0,
                        totalFail: resStats.data.totalFail || 0
                    },
                    trend: { labels: resTrend.data.labels || [], datasets: datasetsWithColor }
                };
            } catch (e) {
                console.error("Fetch Error", e);
                return { faqs: {}, stats: {}, trend: { labels: [], datasets: [] } };
            }
        };

        // --- Search Handler ---
        const handleSearch = async () => {
            // 1. Period 1 检查 (保持不变)
            // 只有当 Period 1 是 "Custom Range" 且 "没填日期" 时，才清空并停止
            if (filterA.type === 'custom-range' && (!filterA.start || !filterA.end)) {
                Object.assign(dataA, { faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });
                if (isCompareMode.value) {
                    Object.assign(dataB, { faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });
                }
                aiSummary.value = "";
                return; // ⛔️ 停止执行
            }

            // --- 开始请求 ---
            aiSummary.value = "";
            loading.value = true;

            // 2. Fetch Period A (总是请求)
            const resA = await fetchDataInternal(filterA.type, filterA.start, filterA.end);
            Object.assign(dataA, resA);

            // 3. Fetch Period B (🔥 核心修改部分)
            if (isCompareMode.value) {
                // 判断 Period 2 是否有效：
                // 情况一：类型不是 'custom-range' (例如 weekly)，直接有效
                // 情况二：类型是 'custom-range'，那必须有 start 和 end 日期才有效
                const isValidB = filterB.type !== 'custom-range' || (filterB.start && filterB.end);

                if (isValidB) {
                    // ✅ 有效：传入 filterB.type (不再写死 'custom-range')
                    const resB = await fetchDataInternal(filterB.type, filterB.start, filterB.end);
                    Object.assign(dataB, resB);
                } else {
                    // ❌ 无效 (选了 Custom 但没填日期)：清空 B 数据
                    Object.assign(dataB, { faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });
                }
            } else {
                // 没开启对比模式：清空 B 数据
                Object.assign(dataB, { faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });
            }

            loading.value = false;
        };

        watch(() => filterA.type, (newVal) => {
            if(newVal !== 'custom-range') handleSearch();
        });

        watch(() => filterB.type, (newVal) => {
            // 只有开启了对比模式且不是 custom-range 时才自动搜索
            if (isCompareMode.value && newVal !== 'custom-range') {
                handleSearch();
            }
        });

        watch(isCompareMode, (newVal) => {
            aiSummary.value = "";

            if (newVal) {
                // 🟢 开启对比模式
                // 1. 设置类型为 custom-range
                filterA.type = 'custom-range';
                filterB.type = 'custom-range';
                // 2. 清空日期
                filterA.start = null;
                filterA.end = null;
                filterB.start = null;
                filterB.end = null;
                // 3. 彻底清空所有数据
                Object.assign(dataA, { faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });
                Object.assign(dataB, { faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });

                // ❌ 关键：这里不要调用 handleSearch()！
                // 因为日期是空的，handleSearch 会被上面的逻辑拦截（或者清空数据），
                // 只要我们手动清空了数据，就不需要调 search 了。

            } else {
                // 🔴 关闭对比模式
                // 切回 All Time
                filterA.type = 'all-time';
                // 这里 filterA.type 的变化会触发下面的 watcher，从而自动调用 handleSearch
                // 所以这里也不需要手动调 handleSearch，让 watcher 去做
            }
        });

        watch(() => isCompareMode.value, (newVal) => { if(!newVal) handleSearch(); }); // Optional: auto-refresh on toggle off

        // --- Export Excel ---
        const exportToExcel = () => {
            const wb = XLSX.utils.book_new();

            // 1. Summary
            const summaryRows = [
                ["Report Generated", new Date().toLocaleString()],
                ["Mode", isCompareMode.value ? "Comparison" : "Single Period"],
                // 🔥 修改：使用 period1Label.value
                ["Period 1", period1Label.value],
                // 🔥 修改：使用 period2Label.value
                isCompareMode.value ? ["Period 2", period2Label.value] : [],
                [],
                ["Metric", "Period 1", isCompareMode.value ? "Period 2" : "", isCompareMode.value ? "Difference (Period 1 minus Period 2)" : ""],
                ["Total Queries", dataA.stats.totalQuestions, isCompareMode.value ? dataB.stats.totalQuestions : "", isCompareMode.value ? dataA.stats.totalQuestions - dataB.stats.totalQuestions : ""],
                ["Success", dataA.stats.totalSuccess, isCompareMode.value ? dataB.stats.totalSuccess : "", ""],
                ["Failed", dataA.stats.totalFail, isCompareMode.value ? dataB.stats.totalFail : "", ""]
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
                    "Period 1": valA,
                    ...(isCompareMode.value && { "Period 2": valB, "Difference (Period 1 minus Period 2)": valA - valB })
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
                    "Period 1": valA,
                    ...(isCompareMode.value && { "Period 2": valB, "Difference (Period 1 minus Period 2)": valA - valB })
                };
            });

            XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(deptRows), "Departments");
            // 3. FAQs (Side by Side)
            const faqRows = [];
            const maxLen = Math.max(dataA.faqs.Faq?.length || 0, dataB.faqs.Faq?.length || 0);
            for(let i=0; i<maxLen; i++) {
                const row = {};
                if(dataA.faqs.Faq?.[i]) {
                    row["P1 Rank"] = i+1;
                    row["P1 Question"] = dataA.faqs.Faq[i].question;
                    row["P1 Count"] = dataA.faqs.Faq[i].total;
                }
                if(isCompareMode.value && dataB.faqs.Faq?.[i]) {
                    row["|"] = "|"; // Separator
                    row["P2 Rank"] = i+1;
                    row["P2 Question"] = dataB.faqs.Faq[i].question;
                    row["P2 Count"] = dataB.faqs.Faq[i].total;
                    row["Difference (Period 1 minus Period 2)"] = dataA.faqs.Faq[i].total - dataB.faqs.Faq[i].total;
                }
                faqRows.push(row);
            }
            const wsFaq = XLSX.utils.json_to_sheet(faqRows);
            wsFaq['!cols'] = [{ wch: 5 }, { wch: 40 }, { wch: 10 }, { wch: 2 }, { wch: 5 }, { wch: 40 }, { wch: 10 }];
            XLSX.utils.book_append_sheet(wb, wsFaq, "FAQs");

            XLSX.writeFile(wb, `Report_${new Date().toISOString().slice(0,10)}.xlsx`);
        };

        // --- Export PDF ---
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

        // --- AI Analysis ---
        const generateAnalysis = async () => {
            analyzing.value = true;
            try {
                const payload = {
                    mode: isCompareMode.value ? 'comparison' : 'single',
                    period1: {
                        // 🔥 修改：使用 period1Label.value 获取可读的日期描述
                        filter: period1Label.value,
                        stats: dataA.stats,
                        topIntent: dataA.faqs.Intent?.[0]?.intent_name
                    },
                    period2: isCompareMode.value ? {
                        // 🔥 修改：使用 period2Label.value
                        range: period2Label.value,
                        stats: dataB.stats,
                        topIntent: dataB.faqs.Intent?.[0]?.intent_name
                    } : null
                };
                const res = await axios.post('/api/generate-summary', { stats: payload }, { headers: { Authorization: `Bearer ${token}` } });
                aiSummary.value = res.data.summary;
            } catch(e) {
                alert("AI Error");
            } finally {
                analyzing.value = false;
            }
        };

        const triggerAnalysis = async () => {
            // 1. 滚动到底部，让用户看到 Loading
            setTimeout(() => {
                const el = document.getElementById('ai-result-section');
                if(el) el.scrollIntoView({ behavior: 'smooth' });
            }, 100);

            // 2. 开始生成
            await generateAnalysis();
        };

            // 滚到底部函数
        const scrollToBottom = () => {
            if (aiSectionRef.value) {
                aiSectionRef.value.scrollIntoView({ behavior: 'smooth' });
            }
        };

        // 监听底部区域可见性的逻辑
        const setupObserver = () => {
            // 创建观察器
            observer = new IntersectionObserver((entries) => {
                const entry = entries[0];
                // 如果底部区域正在屏幕内 (isIntersecting 为 true)，则隐藏悬浮按钮
                // 如果底部区域跑出了屏幕 (用户向上划了)，则显示悬浮按钮
                showFab.value = !entry.isIntersecting;
            }, {
                root: null, // 视口
                threshold: 0.1 // 只要底部区域出现 10%，就算“可见”
            });

            if (aiSectionRef.value) {
                observer.observe(aiSectionRef.value);
            }
        };

        const barChartOptions = ref({ responsive: true, maintainAspectRatio: false, plugins: { legend: {display: false}, datalabels: {color: '#fff'} }, scales: { y: {beginAtZero: true, ticks:{precision:0}}} });
        const lineChartOptions = ref({ responsive: true, maintainAspectRatio: false, plugins: { legend: {position: 'top'} }, scales: { y: {beginAtZero: true, ticks:{precision:0}}} });

        onMounted(() => {
            handleSearch();
            setupObserver();
        });

        onUnmounted(() => {
        // 销毁观察器，防止内存泄漏
        if (observer) observer.disconnect();
        });

        return {
            loading, exporting, isCompareMode, analyzing, aiSummary,
            aiSectionRef, showFab, scrollToBottom,
            filterA, filterB, dataA, dataB,
            chartDataA, chartDataB,
            barChartOptions, lineChartOptions,
            handleSearch, fetchCustomRangeData: handleSearch,
            exportToExcel, exportToPDF, generateAnalysis, getDiff, triggerAnalysis,
             period1Label, period2Label
        };
    }
}
</script>

<style>
/* 保持原有样式，微调 Metric Card 以适应小字对比 */
.metric-card.card {
    border: none;
    padding: 10px;
    height: 120px; /* 固定高度确保整齐 */
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.metric-title {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 2px;
}
.metric-value {
    font-size: 2.2rem !important;
    line-height: 1;
}
.bg-primary { background-color: #2c3e50 !important; }
.bg-success { background-color: #1abc9c !important; }
.bg-info { background-color: #3498db !important; }
.bg-purple { background-color: #9b59b6 !important; }
</style>
