<template>
  <div class="flex h-screen">
    <aside class="w-64 bg-gray-800 text-white p-4">
      <h2 class="text-xl font-bold mb-6">Admin Panel</h2>
      <nav class="flex flex-col space-y-4">
        <RouterLink to="/admin/faqs" class="hover:bg-gray-700 p-2 rounded">Manage FAQs</RouterLink>
        <RouterLink to="/admin/intents" class="hover:bg-gray-700 p-2 rounded">Manage Intents</RouterLink>
        <RouterLink to="/admin/departments" class="hover:bg-gray-700 p-2 rounded">Manage Departments</RouterLink>
        <button v-if="isLoggedIn" @click="handleLogout" class="hover:bg-red-700 p-2 rounded text-left">Logout</button>
        <RouterLink v-else to="/login" class="hover:bg-gray-700 p-2 rounded">Login</RouterLink>
      </nav>
    </aside>

    <main class="flex-1 p-6 overflow-y-auto">
      <RouterView />
    </main>
  </div>
</template>

<script>
export default {
  computed: {
    isLoggedIn() {
      // Return a boolean based on whether the token exists
      return !!localStorage.getItem('sanctum_token');
    }
  },
  methods: {
    handleLogout() {
      // 1. Remove the token from local storage
      localStorage.removeItem('sanctum_token');
      // 2. Redirect the user back to the login page
      this.$router.push('/login');
    }
  }
}
</script>
