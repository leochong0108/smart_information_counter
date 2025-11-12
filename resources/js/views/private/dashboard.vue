<template>
<div class="container-fluid mt-4">
    <div class="row">
    <div class="col-12 col-md-3 mb-3">
            <div class="metric-card card text-white bg-primary">
                <div class="card-body">
                    <div class="metric-title">Total Queries</div>
                    <div class="metric-value display-4 font-weight-bold">
                        {{ totalQuestions }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3 mb-3">
            <div class="metric-card card text-white bg-success">
                <div class="card-body">
                    <div class="metric-title">Total Like</div>
                    <div class="metric-value display-4 font-weight-bold">
                        17.6K
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3 mb-3">
            <div class="metric-card card text-white bg-info">
                <div class="card-body">
                    <div class="metric-title">Total Success</div>
                    <div class="metric-value display-4 font-weight-bold">
                        {{ totalQuestions }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3 mb-3">
            <div class="metric-card card text-white bg-purple">
                <div class="card-body">
                    <div class="metric-title">Total Failed</div>
                    <div class="metric-value display-4 font-weight-bold">
                        {{ totalFail }}
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="row">
    <div class="col-12 col-md-6 mt-4">
        <div class="card p-1">
            <h3>Total Intent Queries Bar Chart</h3>
            <div style="height: auto; ">
                <BarChart
                    :chart-data="chartData"
                    :options="barChartOptions"
                    v-if="chartData.datasets.length && chartData.datasets[0].data.length"
                />
                <p v-else class="text-center">No intent data available for chart.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 mt-4">
        <div class="card p-1">
            <h3>Total Intent Queries Pie chart</h3>
            <div style="height: auto; ">
                <PieChart
                    :chart-data="chartData"
                    :options="pieChartOptions"
                    v-if="chartData.datasets.length && chartData.datasets[0].data.length"
                />
                <p v-else class="text-center">No intent data available for chart.</p>
            </div>
        </div>
    </div>
</div>

    <div class="row">
        <div class="col-12 col-md-6 mt-4">
            <div class="card p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <h3>Top 10 FAQs</h3>
                <div>
                    <select v-model="selectedFilter" class="form-select me-2" style="width: auto;">
                        <option value="all-time">All Time</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
            </div>

                <div v-if="loading" class="text-center" style="padding: 13rem;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div v-if="!top10FAQs.length">
                    <p>No Data available. Please add some.</p>
                </div>

                <div v-if="top10FAQs.length" >
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">#</th> <th scope="col">Question</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(TOP, index) in top10FAQs" :key="TOP.id">
                                <td>{{ index + 1 }}</td> <td>{{ TOP.question }}</td>
                                <td>{{ TOP.total }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <div class="col-12 col-md-6 mt-4">
            <div class="card p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h3>Top 10 FAQs</h3>
                    <div>
                        <select v-model="selectedFilter" class="form-select me-2" style="width: auto;">
                            <option value="all-time">All Time</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>

                <div v-if="loading" class="text-center" style="padding: 13rem;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div v-if="!top10FAQs.length">
                    <p>No Data available. Please add some.</p>
                </div>

                <div v-if="top10FAQs.length" >
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Question</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="TOP in top10FAQs" :key="TOP.id">
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

Chart.register(...registerables, ChartDataLabels);

export default {

    components: {
        BarChart,
        PieChart,
    },

    setup() {

        const {
            totalQuestions,
            totalFail,
            getTotalQuestions,
            getTotalFail

        }
        = useDataFetcher();

        const top10FAQs = ref([]);
        const totalIntents = ref([]);
        const loading = ref(true);
        const selectedFilter = ref('all-time');
        const error = ref(null);
        const token = localStorage.getItem('sanctum_token');

        const chartData = computed(() => {
            if(!totalIntents.value || !Array.isArray(totalIntents.value)|| totalIntents.value.length === 0){
                return { labels: [], datasets: []};
            }

            const labels = totalIntents.value.map(item => item.intent_name);
            const data = totalIntents.value.map(item => item.total);

            const backgroundColors = [
                '#0d6efd', '#6f42c1', '#dc3545', '#fd7e14', '#ffc107',
                '#198754', '#20c997', '#0dcaf0', '#adb5bd', '#343a40'
            ];

            return {
                labels: labels,
                datasets: [
                    {
                        // This will be used for both bar and pie charts
                        label: 'Total Queries per Intent',
                        backgroundColor: backgroundColors.slice(0, data.length), // Use one color per bar/slice
                        data: data,
                        // Only needed for Bar Chart (optional styling)
                        borderColor: 'rgba(0,0,0,0.1)',
                        borderWidth: 1
                    },
                ]
            };

        });

        const barChartOptions = ref({
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Query Count'
                    },
                    // Ensure integer ticks for counts
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false // Hide legend for bar chart
                },
                title: {
                    display: false,
                }
            }
        });

        const pieChartOptions = ref({
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right', // Place legend on the side
                },
                title: {
                    display: false,
                },
            datalabels: {
                    color: '#fff', // White text for visibility
                    // Function to calculate and format the percentage
                    formatter: (value, ctx) => {
                        let sum = 0;
                        // Use the data array from the first dataset
                        let dataArr = ctx.chart.data.datasets[0].data;

                        dataArr.forEach(data => {
                            sum += data;
                        });

                        // Calculate percentage, round to one decimal, and add '%' symbol
                        let percentage = (value * 100 / sum).toFixed(1) + "%";
                        return percentage;
                    },
                    font: {
                        weight: 'bold' // Bold font for better visibility
                    }
                }
            }
        });

        const getTop10FAQs = async (filter) => {
            if (token) {
                loading.value = true;
                error.value = null;
                try {
                    // ⚙️ Pass the filter as a query parameter
                    const response = await axios.get(`/api/top10Faqs?filter=${filter}`, {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    top10FAQs.value = response.data;
                    console.log(top10FAQs.value);
                } catch (err) {
                    error.value = err.response?.data?.message || 'Error fetching top 10 FAQs';
                } finally {
                    loading.value = false;
                }
            } else {
                loading.value = false;
            }
        };

        const getTotalIntents = async () => {
            if (token) {
                try {
                    const response = await axios.get('/api/totalIntents', {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    totalIntents.value = response.data;
                    console.log(totalIntents.value);
                } catch (err) {
                    error.value = err.response?.data?.message || 'Error fetching total intents';
                }
            }
        };

        // 🔄 Watch for changes in selectedFilter and fetch new data
        watch(selectedFilter, (newFilter) => {
            getTop10FAQs(newFilter);
        }, { immediate: true }); // immediate: true fetches data on component mount

        onMounted(async () => {
 /*            await getFAQs();
            await getDepartments();
            await getIntents(); */
            await getTotalIntents();
            await getTotalQuestions();
            await getTotalFail();
        });

        return {
            totalIntents,
            totalQuestions,
            top10FAQs,
            selectedFilter,
            loading,
            error,
            barChartOptions,
            pieChartOptions,
            chartData,
            totalFail,
            getTop10FAQs,
            getTotalQuestions,
            getTotalIntents,
            getTotalFail
        };
    }
}
</script>

<style>
.metric-card.card {
    /* Removes standard borders for a cleaner look */
    border: none;
    /* Padding adjustments */
    padding: 10px;
    height: 120px; /* Gives a fixed size for uniformity */
    /* Optional: Slight border radius for modern feel */
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.metric-title {
    /* Title is smaller and sits on top */
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.8); /* Slightly faded white for contrast */
    margin-bottom: 5px; /* Space between title and value */
}

.metric-value {
    /* The large number */
    font-size: 2.5rem !important; /* Override Bootstrap's display-4 if needed */
    line-height: 1; /* Ensure tight spacing */
}

/* Customize colors to match the image's vibrant look */
.bg-primary {
    background-color: #2c3e50 !important; /* Dark blue/grey */
}
.bg-success {
    background-color: #1abc9c !important; /* Teal/Green */
}
.bg-info {
    background-color: #3498db !important; /* Bright Blue */
}
.bg-purple {
    background-color: #9b59b6 !important; /* Purple */
}
</style>
