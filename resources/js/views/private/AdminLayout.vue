<!-- <template>
  <div class="flex h-screen">
    <aside class="w-64 bg-gray-800 text-white p-4">
      <h2 class="text-xl font-bold mb-6">Admin Panel</h2>
      <nav class="flex flex-col space-y-4">
        <RouterLink to="/admin/faqs" class="hover:bg-gray-700 p-2 rounded">Manage FAQs</RouterLink>
        <RouterLink to="/admin/intents" class="hover:bg-gray-700 p-2 rounded">Manage Intents</RouterLink>
        <RouterLink to="/admin/departments" class="hover:bg-gray-700 p-2 rounded">Manage Departments</RouterLink>
        <button v-if="isLoggedIn" @click="handleLogout" class="hover:bg-red-700 p-2 rounded text-left">Logout</button>
        <RouterLink v-else to="/login" class="hover:bg-gray-700 p-2 rounded">Login</RouterLink>
        <RouterLink to="/" class="hover:bg-gray-700 p-2 rounded"><button  class='btn btn-primary'>To Chat</button></RouterLink>


      </nav>
    </aside>

    <main class="flex-1 p-6 overflow-y-auto">
      <RouterView />
    </main>
  </div>
</template> -->

<template>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
            <h1 class="navbar-brand" >
                <RouterLink to="/admin/" class="nav-link active" aria-current="page">
                    Admin Panel
                </RouterLink>
            </h1>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                <RouterLink to="/admin/departments" class="nav-link active" aria-current="page">
                    Departments
                </RouterLink>
                </li>


                <li class="nav-item">
                <RouterLink to="/admin/intents" class="nav-link active" aria-current="page">
                    Intents
                </RouterLink>
                </li>


                <li class="nav-item">
                <RouterLink to="/admin/faqs" class="nav-link active" aria-current="page">
                    FAQs
                </RouterLink>
                </li>




            </ul>
            <form class="d-flex" >
                 <button v-if="isLoggedIn" @click="handleLogout" class="btn btn-danger" style= "margin-right: 10px;">Logout</button>
                 <RouterLink v-else to="/login" class="hover:bg-gray-700 p-2 rounded">Login</RouterLink>
                 <RouterLink to="/" class=""><button  class='btn btn-primary'>To Chat</button></RouterLink>
            </form>
            </div>
        </div>
    </nav>

    <main class="container-fluid">
      <RouterView />
    </main>

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
