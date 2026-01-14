import { ref, computed } from 'vue';

/**
 *
 * @param {Array<Object>} dataArrayRef
 * @param {Array<Object>} filterOptions
 * @param {string} filterKey
 * @param {Array<string>} searchKeys
 * @returns {{
 * searchTerm: Ref<string>,
 * selectedFilterId: Ref<string|number>,
 * filteredData: ComputedRef<Array<Object>>
 * }}
 */

export function useFilterableData(dataArrayRef, filterOptions, filterKey, searchKeys) {

    const searchTerm = ref('');
    const selectedFilterId = ref('');

    const filteredData = computed(() => {
        let data = dataArrayRef.value;

        if (searchTerm.value) {
            const searchLower = searchTerm.value.toLowerCase();

            data = data.filter(item => {
                return searchKeys.some(key => {
                    const value = item[key];
                    return value && value.toString().toLowerCase().includes(searchLower);
                });
            });
        }

        if (selectedFilterId.value) {
            const filterId = selectedFilterId.value;

            data = data.filter(item => {
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
