<template>
<div class="container-fluid mt-4">
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
            <div class="metric-card card bg-light mb-2 p-2 ">
                <div class="d-flex flex-column">
                    <div class="d-flex align-items-center">
                     <select v-model="selectedFilter" class="form-select form-select-sm">
                            <option value="all-time">All Time</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
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
                    <div class="d-flex align-items-center ">
                        <button @click="fetchCustomRangeData" class="btn btn-sm btn-primary flex-shrink-0 me-1"><i class="bi bi-search me-1"></i>Search</button>
                        <button @click="exportAllData" class="btn btn-sm btn-success flex-shrink-0 ">
                            <i class="bi bi-download me-1"></i> Export All Data
                        </button>
                    </div>
                </div>
            </div>


        </div>
    </div>

<div class="row">
    <div class="col-12 col-md-6 mt-4">
        <div class="card p-1">
            <h3>Intent Queries</h3>
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
            <h3>Department Queries</h3>
            <div style="height: auto; ">
                <PieChart
                    :chart-data="departmentChartData"
                    :options="pieChartOptions"
                    v-if="departmentChartData.datasets.length && departmentChartData.datasets[0].data.length"
                />
                <p v-else class="text-center">No department data available for chart.</p>
            </div>
        </div>
    </div>
</div>

    <div class="row">
        <div class="col-12 col-md-6 mt-4">
            <div class="card p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <h3>Top 10 FAQs</h3>
            </div>

                <div v-if="loading" class="text-center" style="padding: 13rem;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div v-if="!top10FAQs.Faq?.length">
                    <p>No Data available. Please add some.</p>
                </div>

                <div v-if="top10FAQs.Faq?.length" >
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
            <div class="card p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h3>Top 10</h3>
                </div>

                <div v-if="loading" class="text-center" style="padding: 13rem;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div v-if="!top10FAQs.Faq?.length">
                    <p>No Data available. Please add some.</p>
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

        }
        = useDataFetcher();

        const top10FAQs = ref({});
       // const totalIntents = ref([]);
        const loading = ref(true);
        const selectedFilter = ref('all-time');
        const error = ref(null);
        const token = localStorage.getItem('sanctum_token');
        const customStartDate = ref(null);
        const customEndDate = ref(null);

        const getRandomColor = () => {
            // Generate a random number up to 16777215 (which is FFFFFF in hex)
            // Convert it to a hexadecimal string
            // Pad the start with zeros to ensure it's always 6 characters long
            return '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
        };

        const chartData = computed(() => {
            const inetentData = top10FAQs.value.Intent;

            if(!inetentData || !Array.isArray(inetentData)|| inetentData.length === 0){
                return { labels: [], datasets: []};
            }

            const labels = inetentData.map(item => item.intent_name);
            const data = inetentData.map(item => item.total);

/*             const backgroundColors = [
                '#0d6efd', '#6f42c1', '#dc3545', '#fd7e14', '#ffc107',
                '#198754', '#20c997', '#0dcaf0', '#adb5bd', '#343a40'
            ]; */
            const randomColors = data.map(() => getRandomColor());

            return {
                labels: labels,
                datasets: [
                    {
                        // This will be used for both bar and pie charts
                        label: 'Total Queries per Intent',
                        //backgroundColor: backgroundColors.slice(0, data.length), // Use one color per bar/slice
                        backgroundColor: randomColors,
                        data: data,
                        // Only needed for Bar Chart (optional styling)
                        borderColor: 'rgba(0,0,0,0.1)',
                        borderWidth: 1,
                    },
                ]
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
                datasets: [
                    {
                        // This will be used for both bar and pie charts
                        label: 'Total Queries per Department',
                        backgroundColor: randomColors, // Use one color per bar/slice
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
                x: { stacked: true },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Query Count',
                    },
                    // Ensure integer ticks for counts
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                datalabels: {
                    color: '#fff', // Set font color to White
                    font: {
                        weight: 'bold' // Set font weight to Bold
                    }
                },
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
                        return `${value} times\n(${percentage})`;
                    },
                    font: {
                        weight: 'bold' // Bold font for better visibility
                    }
                },
                tooltip: {
        callbacks: {
            label: function(context) {
                // This creates the custom text in the tooltip box
                const label = context.label || '';
                const total = context.formattedValue; // The raw count
                const percentage = (context.parsed / context.dataset.data.reduce((a, b) => a + b, 0) * 100).toFixed(1);

                return `${label}: ${total} (${percentage}%)`;
            }
        }
    }
            }
        });

        const getTop10FAQs = async (filter) => {
            if (token) {
                loading.value = true;
                error.value = null;
                try {
                    // 🏗️ Build query string
                    let apiUrl = `/api/top10Faqs?filter=${filter}`;

                    // ✨ Conditional addition of date range to query string
                    if (filter === 'custom-range' && customStartDate.value && customEndDate.value) {
                        apiUrl += `&startDate=${customStartDate.value}&endDate=${customEndDate.value}`;
                    }

                    const response = await axios.get(apiUrl, {
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

const fetchCustomRangeData = () => {
    if (customStartDate.value && customEndDate.value) {
        // Force the filter to 'custom-range' for the API call
        getTop10FAQs('custom-range', customStartDate.value, customEndDate.value);
    } else {
        alert('Please select both a start and end date for the custom range.');
    }
};

/*         const getTotalIntents = async () => {
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
        }; */

// 🔄 Watch for changes in selectedFilter
        watch(selectedFilter, (newFilter) => {
            // Only automatically fetch data for preset filters
            if (newFilter !== 'custom-range') {
                getTop10FAQs(newFilter);
            }
            // For 'custom-range', the user must click 'Go' (via fetchCustomRangeData)
            // to prevent fetching with null dates immediately upon selection.
        }, { immediate: true });

        onMounted(async () => {
 /*            await getFAQs();
            await getDepartments();
            await getIntents(); */
            //await getTotalIntents();
            await getTotalQuestions();
        });

        return {
            //totalIntents,
            totalQuestions,
            top10FAQs,
            selectedFilter,
            loading,
            error,
            barChartOptions,
            pieChartOptions,
            chartData,
            departmentChartData,
            getTop10FAQs,
            getTotalQuestions,
            //getTotalIntents,
            customStartDate, // Make new refs available to the template
            customEndDate,
            fetchCustomRangeData, // Make new function available to the template
            getRandomColor,
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
