import { reactive, ref, computed, watch } from 'vue';
import axios from 'axios';

export function useDashboardData() {
    const token = localStorage.getItem('sanctum_token');
    const loading = ref(false);
    const isCompareMode = ref(false);

    const filterA = reactive({ type: 'all-time', start: null, end: null });
    const filterB = reactive({ type: 'all-time', start: null, end: null });

    const dataA = reactive({ faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });
    const dataB = reactive({ faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });

    const getRandomColor = () => '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');

    const fetchDataInternal = async (filterType, startDate, endDate) => {
        let q = `?filter=${filterType}`;
        if (filterType === 'custom-range' && startDate && endDate) {
            q += `&startDate=${startDate}&endDate=${endDate}`;
        }
        try {
            const [resFaqs, resTrend, resStats] = await Promise.all([
                axios.get(`/api/top10Faqs${q}`, { headers: { Authorization: `Bearer ${token}` } }),
                axios.get(`/api/department-trend${q}`, { headers: { Authorization: `Bearer ${token}` } }),
                axios.get(`/api/getDashboardMetrics${q}`, { headers: { Authorization: `Bearer ${token}` } })
            ]);

            const ds = resTrend.data.datasets ? resTrend.data.datasets.map(d => ({
                ...d,
                borderColor: getRandomColor(),
                backgroundColor: 'transparent',
                tension: 0.3, borderWidth: 2, pointRadius: 3
            })) : [];

            return {
                faqs: resFaqs.data,
                stats: resStats.data,
                trend: { labels: resTrend.data.labels || [], datasets: ds }
            };
        } catch (e) {
            console.error(e);
            return { faqs: {}, stats: {}, trend: { labels: [], datasets: [] } };
        }
    };

    const handleSearch = async () => {
        if (filterA.type === 'custom-range' && (!filterA.start || !filterA.end)) return;
        loading.value = true;

        const promises = [fetchDataInternal(filterA.type, filterA.start, filterA.end)];
        if (isCompareMode.value) {
            promises.push(fetchDataInternal(filterB.type, filterB.start, filterB.end));
        }

        const [resA, resB] = await Promise.all(promises);

        Object.assign(dataA, resA);
        if (isCompareMode.value && resB) {
            Object.assign(dataB, resB);
        } else {
            Object.assign(dataB, { faqs: {}, stats: {}, trend: { labels: [], datasets: [] } });
        }

        loading.value = false;
    };

    const formatChartData = (sourceData, sourceTrend) => {
        const intentRaw = sourceData.Intent || [];
        return {
            intents: {
                labels: intentRaw.map(i => i.intent_name),
                datasets: [{
                    label: 'Queries',
                    data: intentRaw.map(i => i.total),
                    backgroundColor: intentRaw.map(() => getRandomColor()),
                    barPercentage: 0.6, borderRadius: 4
                }]
            },
            trends: sourceTrend
        };
    };

    const chartDataA = computed(() => formatChartData(dataA.faqs, dataA.trend));
    const chartDataB = computed(() => formatChartData(dataB.faqs, dataB.trend));

    const getPeriodLabel = (filter) => {
        if (filter.type === 'custom-range' && filter.start && filter.end) return `${filter.start} ~ ${filter.end}`;
        return filter.type.charAt(0).toUpperCase() + filter.type.slice(1);
    };
    const period1Label = computed(() => getPeriodLabel(filterA));
    const period2Label = computed(() => getPeriodLabel(filterB));

    watch(() => filterA.type, (v) => { if(v !== 'custom-range') handleSearch(); });
    watch(() => filterB.type, (v) => { if(isCompareMode.value && v !== 'custom-range') handleSearch(); });
    watch(isCompareMode, (v) => {
        if (!v) filterA.type = 'all-time';
    });

    return {
        loading, isCompareMode,
        filterA, filterB,
        dataA, dataB,
        chartDataA, chartDataB,
        period1Label, period2Label,
        handleSearch
    };
}
