<template>
<div class="container-fluid mt-4" id="dashboard-content">

    <div class="row mb-3" v-if="exporting">
        <div class="col-12">
            <h2 class="text-center">Dashboard Report</h2>
            <p class="text-center text-muted">Generated on: {{ new Date().toLocaleString() }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-3 mb-1">
            <div class="metric-card card text-white bg-primary">
                <div class="card-body">
                    <div class="metric-title">Total Queries</div>
                    <div class="metric-value display-4 font-weight-bold">
                        {{ totalQuestions.totalQuestions }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3 mb-1">
            <div class="metric-card card text-white bg-info">
                <div class="card-body">
                    <div class="metric-title">Total Success</div>
                    <div class="metric-value display-4 font-weight-bold">
                        {{ totalQuestions.totalSuccess }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3 mb-1">
            <div class="metric-card card text-white bg-purple">
                <div class="card-body">
                    <div class="metric-title">Total Failed</div>
                    <div class="metric-value display-4 font-weight-bold">
                        {{ totalQuestions.totalFail }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3 mb-1 ">
            <div class="metric-card card bg-light mb-2 p-2" data-html2canvas-ignore="true">
                <div class="d-flex flex-column">
                    <div class="d-flex align-items-center">
                     <select v-model="selectedFilter" class="form-select form-select-sm">
                            <option value="all-time">All Time</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="custom-range">Custom Range</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex flex-column mt-1">
                    <div class="d-flex align-items-center">
                        <input type="date" v-model="customStartDate" class="form-control form-control-sm me-1">
                         <input type="date" v-model="customEndDate" class="form-control form-control-sm ">
                    </div>
                </div>

                <div class="d-flex flex-column mt-1">
                    <div class="d-flex align-items-center justify-content-between">
                        <button @click="fetchCustomRangeData" class="btn btn-sm btn-primary flex-shrink-0 me-1"><i class="bi bi-search me-1"></i>Search</button>

                        <div class="btn-group w-100">
                            <button type="button" class="btn btn-sm btn-success dropdown-toggle w-100" data-bs-toggle="dropdown" aria-expanded="false" :disabled="loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                <i v-else class="bi bi-download me-1"></i> Export Excel/PDF
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#" @click.prevent="exportToExcel">
                                        <i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Excel (Data)
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="#" @click.prevent="exportToPDF" :disabled="loading">
                                        <span v-if="generatingAnalysis" class="spinner-grow spinner-grow-sm text-light" role="status"></span>
                                        <span v-if="generatingAnalysis"> AI Analyzing...</span>
                                        <span v-else-if="loading"> Generating PDF...</span>
                                        <span v-else><i class="bi bi-file-earmark-pdf"></i> Export PDF with AI</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-6 mt-4">
            <div class="card p-1 h-100">
                <h3 class="p-2">Intent Queries</h3>
                <div style="height: auto; position: relative;">
                    <BarChart
                        :chart-data="chartData"
                        :options="barChartOptions"
                        v-if="chartData.datasets.length && chartData.datasets[0].data.length"
                    />
                    <p v-else class="text-center mt-5">No intent data available for chart.</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 mt-4">
            <div class="card p-1 h-100">
                <h3 class="p-2">Department Queries</h3>
                <div style="height: auto; position: relative;">
                    <PieChart
                        :chart-data="departmentChartData"
                        :options="pieChartOptions"
                        v-if="departmentChartData.datasets.length && departmentChartData.datasets[0].data.length"
                    />
                    <p v-else class="text-center mt-5">No department data available for chart.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-6 mt-4">
            <div class="card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h3>Top 10 FAQs</h3>
                </div>

                <div v-if="loading && !exporting" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div v-else-if="!top10FAQs.Faq?.length">
                    <p>No Data available. Please add some.</p>
                </div>

                <div v-else>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">#</th> <th scope="col">Question</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(TOP, index) in top10FAQs.Faq" :key="TOP.id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ TOP.question }}</td>
                                <td>{{ TOP.total }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 mt-4">
            <div class="card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h3>Department Trends</h3>
                </div>
                <div style="height: auto; position: relative;">
                    <LineChart
                        v-if="trendData.datasets.length"
                        :chart-data="trendData"
                        :options="lineChartOptions"
                    />
                    <p v-else class="text-center mt-5">No trend data available.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4 mb-5"> <div class="col-12">

        <div v-if="!aiSummary && !analyzing" class="text-center">
            <button @click="generateAnalysis" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-stars"></i> Generate AI Performance Analysis
            </button>
        </div>

        <div v-else-if="analyzing" class="text-center p-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Gemini is analyzing your data...</p>
        </div>

        <div v-else class="card border-info shadow-sm">
            <div class="card-header bg-white text-info border-bottom-0 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-robot me-2"></i>AI Executive Summary</span>
                <button @click="generateAnalysis" class="btn btn-sm btn-link text-muted" title="Regenerate">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
            <div class="card-body bg-light-subtle">
                <p class="card-text" style="white-space: pre-line; font-family: sans-serif;">
                    {{ aiSummary }}
                </p>
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
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import { BarChart, PieChart, LineChart } from 'vue-chart-3';
import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { useDataFetcher } from '../../services/dataFetcher';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';

Chart.register(...registerables, ChartDataLabels);

export default {
    components: {
        BarChart,
        PieChart,
        LineChart
    },
    setup() {
        const {
            totalQuestions,
            getTotalQuestions,
        } = useDataFetcher();

        const top10FAQs = ref({});
        const loading = ref(true);
        const exporting = ref(false); // 用于控制导出时的特定UI状态
        const selectedFilter = ref('all-time');
        const error = ref(null);
        const token = localStorage.getItem('sanctum_token');
        const customStartDate = ref(null);
        const customEndDate = ref(null);
        const trendData = ref({ labels: [], datasets: [] });
        const trendLoading = ref(false);
        const aiSummary = ref(""); // 新增：存储 AI 总结
        const generatingAnalysis = ref(false); // 新增：加载状态
        const analyzing = ref(false);

        const getRandomColor = () => {
            return '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
        };

        // 1. 抽取一个辅助函数：准备发给 AI 的数据
        const prepareStatsForAI = () => {
            // 从现有的 top10FAQs 和 totalQuestions 中提取关键信息
            // 避免发送太多无用数据节省 Token
            return {
                period: selectedFilter.value,
                metrics: totalQuestions.value, // total, success, fail
                top_intent: top10FAQs.value.Intent?.[0] || 'N/A', // 第一名的意图
                top_department: top10FAQs.value.Department?.[0] || 'N/A', // 第一名的部门
                top_faq: top10FAQs.value.Faq?.[0]?.question || 'N/A' // 第一名的问题
            };
        };

        const generateAnalysis = async () => {
            analyzing.value = true;
            try {
                const statsPayload = prepareStatsForAI(); // 复用之前的逻辑
                const res = await axios.post('/api/generate-summary', { stats: statsPayload }, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                aiSummary.value = res.data.summary;
            } catch (e) {
                alert("Analysis failed.");
            } finally {
                analyzing.value = false;
            }
        };

        const lineChartOptions = ref({
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index', // 鼠标悬停时显示同一时刻所有部门的数据
                intersect: false,
            },
            plugins: {
                legend: { position: 'top' }, // 显示部门图例
                title: { display: true, text: 'Department Query Trend' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        });

        // 4. 获取 Trend 数据的函数
        const getDepartmentTrend = async (filter) => {
            if (!token) return;
            trendLoading.value = true;
            try {
                let apiUrl = `/api/department-trend?filter=${filter}`;
                if (filter === 'custom-range' && customStartDate.value && customEndDate.value) {
                    apiUrl += `&startDate=${customStartDate.value}&endDate=${customEndDate.value}`;
                }

                const response = await axios.get(apiUrl, {
                    headers: { Authorization: `Bearer ${token}` }
                });

                // 为每个 Dataset 添加颜色 (因为后端没传颜色)
                const datasetsWithColor = response.data.datasets.map(ds => ({
                    ...ds,
                    borderColor: getRandomColor(), // 使用你现有的随机颜色函数
                    backgroundColor: 'transparent', // 折线图通常背景透明
                    tension: 0.3 // 让线条稍微圆滑一点
                }));

                trendData.value = {
                    labels: response.data.labels,
                    datasets: datasetsWithColor
                };

            } catch (err) {
                console.error("Trend Error", err);
            } finally {
                trendLoading.value = false;
            }
        };

        // --- Charts Logic ---
        const chartData = computed(() => {
            const inetentData = top10FAQs.value.Intent;
            if(!inetentData || !Array.isArray(inetentData)|| inetentData.length === 0){
                return { labels: [], datasets: []};
            }
            const labels = inetentData.map(item => item.intent_name);
            const data = inetentData.map(item => item.total);
            const randomColors = data.map(() => getRandomColor());

            return {
                labels: labels,
                datasets: [{
                    label: 'Total Queries per Intent',
                    backgroundColor: randomColors,
                    data: data,
                    borderColor: 'rgba(0,0,0,0.1)',
                    borderWidth: 1,
                }]
            };
        });

        const departmentChartData = computed(() => {
            const departmentData = top10FAQs.value.Department;
            if(!departmentData || !Array.isArray(departmentData)|| departmentData.length === 0){
                return { labels: [], datasets: []};
            }
            const labels = departmentData.map(item => item.name);
            const data = departmentData.map(item => item.total);
            const randomColors = data.map(() => getRandomColor());

            return {
                labels: labels,
                datasets: [{
                    label: 'Total Queries per Department',
                    backgroundColor: randomColors,
                    data: data,
                    borderColor: 'rgba(0,0,0,0.1)',
                    borderWidth: 1
                }]
            };
        });

        // --- Chart Options ---
        const barChartOptions = ref({
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
            },
            plugins: {
                datalabels: { color: '#fff', font: { weight: 'bold' } },
                legend: { display: false },
            }
        });

        const pieChartOptions = ref({
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' },
                datalabels: {
                    color: '#fff',
                    formatter: (value, ctx) => {
                        let sum = 0;
                        let dataArr = ctx.chart.data.datasets[0].data;
                        dataArr.forEach(data => sum += data);
                        let percentage = (value * 100 / sum).toFixed(1) + "%";
                        return `${value}\n(${percentage})`;
                    },
                    font: { weight: 'bold' }
                },
            }
        });

        // --- API Logic ---
        const getTop10FAQs = async (filter) => {
            if (token) {
                loading.value = true;
                error.value = null;
                try {
                    let apiUrl = `/api/top10Faqs?filter=${filter}`;
                    if (filter === 'custom-range' && customStartDate.value && customEndDate.value) {
                        apiUrl += `&startDate=${customStartDate.value}&endDate=${customEndDate.value}`;
                    }
                    const response = await axios.get(apiUrl, {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    top10FAQs.value = response.data;
                } catch (err) {
                    error.value = err.response?.data?.message || 'Error fetching data';
                } finally {
                    loading.value = false;
                }
            } else {
                loading.value = false;
            }
        };

// 6. 修改 Custom Range 按钮逻辑
        const fetchCustomRangeData = () => {
            if (customStartDate.value && customEndDate.value) {
                selectedFilter.value = 'custom-range';
                getTop10FAQs('custom-range');
                getDepartmentTrend('custom-range'); // <--- 新增调用
            } else {
                alert('Please select dates');
            }
        };

        // --- EXPORT LOGIC START ---

        // 1. Export to Excel (Data Only)
        const exportToExcel = () => {
            try {
                const wb = XLSX.utils.book_new();

                // Sheet 1: Summary
                const stats = totalQuestions.value || { totalQuestions: 0, totalSuccess: 0, totalFail: 0 };
                const summaryData = [
                    ["Report Generated", new Date().toLocaleString()],
                    ["Filter Applied", selectedFilter.value],
                    ["Date Range", selectedFilter.value === 'custom-range' ? `${customStartDate.value} to ${customEndDate.value}` : "Preset"],
                    [],
                    ["Metric", "Count"],
                    ["Total Queries", stats.totalQuestions],
                    ["Total Success", stats.totalSuccess],
                    ["Total Failed", stats.totalFail],
                ];
                const wsSummary = XLSX.utils.aoa_to_sheet(summaryData);
                wsSummary['!cols'] = [{ wch: 20 }, { wch: 25 }];
                XLSX.utils.book_append_sheet(wb, wsSummary, "Summary");

                // Sheet 2: Intent
                if (top10FAQs.value.Intent?.length) {
                    const intentRows = top10FAQs.value.Intent.map(item => ({
                        "Intent Name": item.intent_name,
                        "Count": item.total
                    }));
                    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(intentRows), "Intents");
                }

                // Sheet 3: Department
                if (top10FAQs.value.Department?.length) {
                    const deptRows = top10FAQs.value.Department.map(item => ({
                        "Department Name": item.name,
                        "Count": item.total
                    }));
                    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(deptRows), "Departments");
                }

                // Sheet 4: FAQs
                if (top10FAQs.value.Faq?.length) {
                    const faqRows = top10FAQs.value.Faq.map((item, index) => ({
                        "Rank": index + 1,
                        "Question": item.question,
                        "Count": item.total
                    }));
                    const wsFaq = XLSX.utils.json_to_sheet(faqRows);
                    wsFaq['!cols'] = [{ wch: 5 }, { wch: 60 }, { wch: 10 }];
                    XLSX.utils.book_append_sheet(wb, wsFaq, "Top FAQs");
                }

                XLSX.writeFile(wb, `Dashboard_Data_${new Date().toISOString().split('T')[0]}.xlsx`);

            } catch (e) {
                console.error("Export Excel Error:", e);
                alert("Export failed");
            }
        };

        // 2. Export to PDF (Visual Report)
        const exportToPDF = async () => {
            loading.value = true;
            exporting.value = true; // 临时状态，可以用来在截图时隐藏一些按钮等

            if (!aiSummary.value) {
                const confirmGen = confirm("Do you want to include AI analysis in the PDF?");
                if (confirmGen) {
                    await generateAnalysis();
                    // 等待 DOM 渲染
                    await new Promise(r => setTimeout(r, 500));
                }
            }

            // 给 Vue 一点时间渲染任何可能因 exporting 状态改变的 UI
            await new Promise(resolve => setTimeout(resolve, 100));

            const element = document.getElementById('dashboard-content');

            if (!element) {
                alert('Element not found');
                loading.value = false;
                exporting.value = false;
                return;
            }

            try {
                const canvas = await html2canvas(element, {
                    scale: 2, // 高分辨率
                    useCORS: true,
                    backgroundColor: '#ffffff', // 确保背景是白色的
                    // ignoreElements: (node) => node.classList.contains('no-export') // 另一种忽略元素的方法
                });

                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('p', 'mm', 'a4');

                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();

                const imgWidth = pdfWidth;
                const imgHeight = (canvas.height * pdfWidth) / canvas.width;

                let heightLeft = imgHeight;
                let position = 0;

                // 第一页
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pdfHeight;

                // 分页逻辑
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pdfHeight;
                }

                pdf.save(`Dashboard_Report_${new Date().toISOString().split('T')[0]}.pdf`);

            } catch (error) {
                console.error('PDF Export Error:', error);
                alert('Failed to generate PDF');
            } finally {
                loading.value = false;
                exporting.value = false;
            }
        };

        // --- EXPORT LOGIC END ---

        watch(selectedFilter, (newFilter) => {
            if (newFilter !== 'custom-range') {
                // 同时调用两个 API
                getTop10FAQs(newFilter);
                getDepartmentTrend(newFilter); // <--- 新增调用
            }
        }, { immediate: true });

        onMounted(async () => {
            await getTotalQuestions();
        });

        return {
            totalQuestions,
            top10FAQs,
            selectedFilter,
            loading,
            exporting,
            customStartDate,
            customEndDate,
            barChartOptions,
            pieChartOptions,
            chartData,
            departmentChartData,
            fetchCustomRangeData,
            exportToExcel, // 返回给 Template
            exportToPDF,   // 返回给 Template
            trendData,
            lineChartOptions,
            getDepartmentTrend,
            aiSummary,
            generatingAnalysis,
            prepareStatsForAI,
            generateAnalysis,
            analyzing
        };
    }
}
</script>

<style>
/* 你之前的样式保持不变 */
.metric-card.card {
    border: none;
    padding: 10px;
    height: 120px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.metric-title {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 5px;
}
.metric-value {
    font-size: 2.5rem !important;
    line-height: 1;
}
.bg-primary { background-color: #2c3e50 !important; }
.bg-success { background-color: #1abc9c !important; }
.bg-info { background-color: #3498db !important; }
.bg-purple { background-color: #9b59b6 !important; }
</style>
