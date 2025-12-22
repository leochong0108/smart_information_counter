import { ref } from 'vue';
import axios from 'axios';

export function useAIAnalysis() {
    const analyzing = ref(false);
    const aiSummary = ref("");
    const token = localStorage.getItem('sanctum_token');

    const generateAnalysis = async (dataA, dataB, isCompareMode, p1Label, p2Label) => {
        analyzing.value = true;
        try {
            const payload = {
                mode: isCompareMode.value ? 'comparison' : 'single',
                period1: {
                    filter: p1Label.value,
                    stats: dataA.stats,
                    topIntent: dataA.faqs.Intent?.[0]?.intent_name
                },
                period2: isCompareMode.value ? {
                    range: p2Label.value,
                    stats: dataB.stats,
                    topIntent: dataB.faqs.Intent?.[0]?.intent_name
                } : null
            };

            const res = await axios.post('/api/generate-summary',
                { stats: payload },
                { headers: { Authorization: `Bearer ${token}` } }
            );
            aiSummary.value = res.data.summary;
        } catch(e) {
            console.error(e);
            aiSummary.value = "Failed to generate AI analysis.";
        } finally {
            analyzing.value = false;
        }
    };

    return {
        analyzing,
        aiSummary,
        generateAnalysis
    };
}
