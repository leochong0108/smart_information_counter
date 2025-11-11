import { ref } from 'vue';
import axios from 'axios';

export function useDataFetcher() {
  const intents = ref([]);
  const FAQs = ref([]);
  const departments = ref([]);
  const totalQuestions = ref([]);
  const isLoading = ref(false);
  const error = ref(null);
  const token = localStorage.getItem('sanctum_token');

  // The function you want to share
       const getIntents = async () => {

        if(token){
                try {
                    const response = await axios.get('/api/allIntents', {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    intents.value = response.data;
                    console.log(intents.value);
                } catch (err) {
                    error.value = err.response?.data?.message || 'Error fetching intents';
                }
            };
        };

        const getFAQs = async () => {

        if(token){
                try {
                    const response = await axios.get('/api/allFaqs', {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    FAQs.value = response.data;
                    console.log(FAQs.value);
                } catch (err) {
                    error.value = err.response?.data?.message || 'Error fetching FAQs';
                }
            };
        };

        const getDepartments = async () => {

        if(token){
                try {
                    const response = await axios.get('/api/allDepartments', {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    departments.value = response.data;
                    console.log(departments.value);
                } catch (err) {
                    error.value = err.response?.data?.message || 'Error fetching departments';
                }
            };
        };

        const getTotalQuestions = async () => {

        if(token){
                try {
                    const response = await axios.get('/api/totalQuestions', {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    totalQuestions.value = response.data;
                    console.log(totalQuestions.value);
                } catch (err) {
                    error.value = err.response?.data?.message || 'Error fetching total questions';
                }
            };
        }



  return {
    intents,
    FAQs,
    departments,
    totalQuestions,
    isLoading,
    error,
    getIntents,
    getFAQs,
    getDepartments,
    getTotalQuestions

  };
}
