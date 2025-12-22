<template>
<div class="container-fluid py-4" id="dashboard-content">

    <!-- 1. Header (Export Only) -->
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
                                <li><a class="dropdown-item" href="#" @click.prevent="handleSmartExportPDF"><i class="bi bi-file-pdf text-danger me-2"></i>PDF Report</a></li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- 3.1 Bar Charts (Intents) -->
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

    <!-- 3.2 Line Charts (Department Trends) - 🔥 之前漏掉的部分已恢复 -->
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

    <!-- 4. Top 10 FAQs -->
    <div class="row g-4 mt-4">
        <!-- Current Period -->
        <div :class="isCompareMode ? 'col-12 col-xl-6' : 'col-12'">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="text-center mb-0 fw-bold">Top 10 FAQs <span v-if="isCompareMode" class="text-muted fw-bold small ms-1">(Current Period: {{ period1Label }})</span></h6>
                </div>
                <div class="card-body p-0">
                    <!-- Desktop Table -->
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr><th class="px-4">#</th><th>Question</th><th class="text-end px-4">Count</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in dataA.faqs.Faq" :key="index">
                                    <td class="px-4 fw-bold text-secondary">{{ index + 1 }}</td>
                                    <td class="text-truncate" style="max-width: 300px;">{{ item.question }}</td>
                                    <td class="text-end px-4 fw-bold text-dark">{{ item.total }}</td>
                                </tr>
                                <tr v-if="!dataA.faqs.Faq?.length"><td colspan="3" class="text-center py-4 text-muted small">No data available</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Mobile List -->
                    <ul class="d-md-none list-group list-group-flush">
                        <li v-for="(item, index) in dataA.faqs.Faq" :key="index" class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="badge bg-light text-dark border me-2">#{{ index + 1 }}</span>
                                <span class="badge bg-primary rounded-pill">{{ item.total }}</span>
                            </div>
                            <div class="mt-2 fw-medium small">{{ item.question }}</div>
                        </li>
                        <li v-if="!dataA.faqs.Faq?.length" class="list-group-item text-center py-4 text-muted small">No data available</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Comparison Period -->
        <div class="col-12 col-xl-6" v-if="isCompareMode">
            <div class="card h-100 shadow-sm border-start border-primary border-4">
                <div class="card-header bg-white py-3">
                    <h6 class="text-center mb-0 fw-bold text-primary">Top 10 FAQs <span class="text-muted fw-bold small ms-1">(Comparison Period: {{ period2Label }})</span></h6>
                </div>
                <div class="card-body p-0">
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-primary bg-opacity-10 text-primary small text-uppercase">
                                <tr><th class="px-4">#</th><th>Question</th><th class="text-end px-4">Count</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in dataB.faqs.Faq" :key="index">
                                    <td class="px-4 fw-bold text-secondary">{{ index + 1 }}</td>
                                    <td class="text-truncate" style="max-width: 300px;">{{ item.question }}</td>
                                    <td class="text-end px-4 fw-bold text-dark">{{ item.total }}</td>
                                </tr>
                                <tr v-if="!dataB.faqs.Faq?.length"><td colspan="3" class="text-center py-4 text-muted small">No data available</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <ul class="d-md-none list-group list-group-flush">
                        <li v-for="(item, index) in dataB.faqs.Faq" :key="index" class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="badge bg-light text-dark border me-2">#{{ index + 1 }}</span>
                                <span class="badge bg-primary rounded-pill">{{ item.total }}</span>
                            </div>
                            <div class="mt-2 fw-medium small">{{ item.question }}</div>
                        </li>
                        <li v-if="!dataB.faqs.Faq?.length" class="list-group-item text-center py-4 text-muted small">No data available</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Floating Action Button (FAB) - 🔥 之前简化了，现在恢复原样 -->
    <div class="position-fixed bottom-0 end-0 p-4" style="z-index: 1050;" v-if="showFab" data-html2canvas-ignore="true">
        <button @click="aiSummary ? scrollToBottom() : triggerAnalysis()"
                class="btn rounded-circle shadow-lg p-3 d-flex align-items-center justify-content-center"
                :class="aiSummary ? 'btn-secondary' : 'btn-primary'"
                style="width: 60px; height: 60px;"
                :title="aiSummary ? 'View Analysis' : 'Go to Generate'">
            <i class="bi" :class="aiSummary ? 'bi-file-text fs-4' : 'bi-stars fs-4'"></i>
        </button>
    </div>

    <!-- 6. AI Result Section -->
    <div class="row mt-3" id="ai-result-section" ref="aiSectionRef">
        <div class="col-12">
            <div v-if="!aiSummary && !analyzing" class="text-center">
                <button @click="triggerAnalysis" class="btn btn-outline-primary btn-lg">
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
                    <button @click="triggerAnalysis" class="btn btn-sm btn-link text-muted" title="Regenerate">
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

<script setup>
import { onMounted, onUnmounted, computed, ref, watch, nextTick } from 'vue';
import { BarChart, LineChart } from 'vue-chart-3'; // 导入 LineChart
import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

// 引入逻辑 Composable
import { useDashboardData } from '../../composables/useDashboardData';
import { useExport } from '../../composables/useExport';
import { useAIAnalysis } from '../../composables/useAIAnalysis';

Chart.register(...registerables, ChartDataLabels);

// 1. Data Logic
const {
    loading, isCompareMode, filterA, filterB,
    dataA, dataB, chartDataA, chartDataB,
    period1Label, period2Label, handleSearch
} = useDashboardData();

// 2. Export Logic
const { exporting, exportToExcel, exportToPDF } = useExport(
    dataA, dataB, isCompareMode, period1Label, period2Label
);

// 3. AI Logic
const { analyzing, aiSummary, generateAnalysis } = useAIAnalysis();
const aiSectionRef = ref(null);
const showFab = ref(false); // 用于控制 FAB 显示
let observer = null;

const scrollToBottom = () => aiSectionRef.value?.scrollIntoView({ behavior: 'smooth' });

const triggerAnalysis = async () => {
    scrollToBottom();
    await generateAnalysis(dataA, dataB, isCompareMode, period1Label, period2Label);
};

// 4. Chart Options (必须保留 LineChart 特定的配置)
const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: {display: false}, datalabels: {color: '#fff', font: {weight: 'bold'}} },
    scales: { y: {beginAtZero: true, grid: {display: false} }, x: {grid: {display: false}} }
};

// 🔥 关键：恢复 LineChart 的配置，确保 Legend 显示在 Top
const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: {position: 'top', labels: {boxWidth: 10, usePointStyle: true}} },
    scales: { y: {beginAtZero: true} }
};

// 5. FAB Observer Setup
const setupObserver = () => {
    // 当 AI 区域出现在视口中时，隐藏悬浮按钮；否则显示
    observer = new IntersectionObserver((e) => {
        showFab.value = !e[0].isIntersecting;
    }, { threshold: 0.1 });

    if (aiSectionRef.value) observer.observe(aiSectionRef.value);
};

watch(
    [
        () => filterA.type, () => filterA.start, () => filterA.end,
        () => filterB.type, () => filterB.start, () => filterB.end,
        isCompareMode
    ],
    () => {
        if (aiSummary.value) {
            aiSummary.value = ""; // 清空旧数据
        }
    }
);

// 🔥🔥🔥 核心修复 2: 智能导出逻辑 (Smart Export) 🔥🔥🔥
const handleSmartExportPDF = async () => {
    // 如果没有 AI 总结，询问用户
    if (!aiSummary.value) {
        const confirmGen = confirm("AI Analysis is missing. Do you want to generate it before exporting?");

        if (confirmGen) {
            // 用户选择生成：先生成
            await triggerAnalysis();

            // 等待 DOM 更新 (因为 AI 文本生成后，页面高度会变，需要等 Vue 渲染完)
            await nextTick();
            // 额外给一点时间让展开动画完成 (可选)
            await new Promise(r => setTimeout(r, 500));
        }
    }

    // 执行真正的导出 (无论是否生成了 AI，或者用户选了 No，都继续导出)
    exportToPDF('dashboard-content');
};

onMounted(() => {
    handleSearch();
    setupObserver();
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});
</script>

<style scoped>
/* 保持原有样式，不做任何删减 */
.bg-purple { background-color: #6f42c1 !important; }
.bg-gradient-primary { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); }
.text-success-light { color: #d1e7dd !important; }
.text-warning-light { color: #fff3cd !important; }

@media (max-width: 768px) {
    .chart-wrapper {
        min-height: 250px;
        height: 280px;
    }
}

.fab-btn {
    width: 56px;
    height: 56px;
    font-size: 1.5rem;
    transition: transform 0.2s;
}
.fab-btn:active { transform: scale(0.9); }

.animate-slide-down {
    animation: slideDown 0.3s ease-out;
}

/* 📱 手机端 Date Picker 样式修复 */
.mobile-date-input {
    color: #212529;
    background-color: #fff;
    min-height: 40px;
    font-size: 1rem;
}
.mobile-date-input:invalid::-webkit-datetime-edit {
    color: transparent;
}
.mobile-date-input:invalid::before {
    content: "Select Date";
    color: #999;
    margin-right: 0.5rem;
    position: absolute;
}
input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: 0.6;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
