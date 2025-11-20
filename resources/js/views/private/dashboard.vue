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
                                <i v-else class="bi bi-download me-1"></i> Export
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#" @click.prevent="exportToExcel">
                                        <i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Excel (Data)
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="#" @click.prevent="exportToPDF">
                                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>PDF (Report)
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
                    <h3>Top 10 List</h3>
                </div>
                 <div v-if="top10FAQs.Faq?.length" >
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Question</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="TOP in top10FAQs.Faq" :key="TOP.id">
                                <td>{{ TOP.question }}</td>
                                <td>{{ TOP.total }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import { BarChart, PieChart } from 'vue-chart-3';
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

        const getRandomColor = () => {
            return '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
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

        const fetchCustomRangeData = () => {
            if (customStartDate.value && customEndDate.value) {
                selectedFilter.value = 'custom-range'; // Force select dropdown
                getTop10FAQs('custom-range');
            } else {
                alert('Please select both a start and end date.');
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
                getTop10FAQs(newFilter);
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
            exportToPDF    // 返回给 Template
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
