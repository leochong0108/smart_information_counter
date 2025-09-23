<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">FAQ Management</h1>
    <button @click="fetchFaqs" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded">Reload FAQs</button>

    <table class="w-full border">
      <thead>
        <tr class="bg-gray-200">
          <th class="p-2 border">Question</th>
          <th class="p-2 border">Answer</th>
          <th class="p-2 border">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="faq in faqs" :key="faq.id">
          <td class="border p-2">{{ faq.question }}</td>
          <td class="border p-2">{{ faq.answer }}</td>
          <td class="border p-2">
            <button class="bg-yellow-500 text-white px-2 py-1 rounded mr-2">Edit</button>
            <button @click="deleteFaq(faq.id)" class="bg-red-500 text-white px-2 py-1 rounded">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const faqs = ref([])

const fetchFaqs = async () => {
  const token = localStorage.getItem('token')
  const res = await axios.get('/api/faqs', {
    headers: { Authorization: `Bearer ${token}` }
  })
  faqs.value = res.data
}

const deleteFaq = async (id) => {
  const token = localStorage.getItem('token')
  await axios.delete(`/api/faqs/${id}`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  fetchFaqs()
}

onMounted(fetchFaqs)
</script>
