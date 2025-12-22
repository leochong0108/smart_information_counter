import { ref, computed } from 'vue';

/**
 * A composable function to add client-side search and filtering logic.
 *
 * @param {Array<Object>} dataArrayRef - The reactive array of all data (e.g., FAQs.value).
 * @param {Array<Object>} filterOptions - An array of filter options (e.g., intents, departments).
 * @param {string} filterKey - The key in the main data array to match the filter option ID (e.g., 'intent_id').
 * @param {Array<string>} searchKeys - An array of keys in the data objects to search against (e.g., ['question', 'answer']).
 * @returns {{
 * searchTerm: Ref<string>,
 * selectedFilterId: Ref<string|number>,
 * filteredData: ComputedRef<Array<Object>>
 * }}
 */
export function useFilterableData(dataArrayRef, filterOptions, filterKey, searchKeys) {

    // 1. State for Search and Filter Input
    const searchTerm = ref('');
    const selectedFilterId = ref(''); // Can hold the ID of the selected intent or department

    // 2. Computed Property for Filtering
    const filteredData = computed(() => {
        let data = dataArrayRef.value;

        // --- Step A: Apply Text Search ---
        if (searchTerm.value) {
            const searchLower = searchTerm.value.toLowerCase();

            data = data.filter(item => {
                // Check if any defined search key matches the term
                return searchKeys.some(key => {
                    const value = item[key];
                    return value && value.toString().toLowerCase().includes(searchLower);
                });
            });
        }

        // --- Step B: Apply Filter Selection ---
        if (selectedFilterId.value) {
            // Treat the selected ID as a string or number for comparison
            const filterId = selectedFilterId.value;

            data = data.filter(item => {
                // We check if the item's foreign key (e.g., item.intent_id) matches the selected filter ID
                return item[filterKey] === filterId;
            });
        }

        return data;
    });

    return {
        searchTerm,
        selectedFilterId,
        filteredData
    };
}
