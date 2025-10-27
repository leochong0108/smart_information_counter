<template>
  <div class="container-fluid d-flex flex-column justify-content-center align-items-center vh-100 position-relative">
    <h1>Smart Information Counter</h1>
    <p>Welcome. Click to start chatting.</p>
    <router-link to="/chat"><button class="btn btn-primary">Open Chat</button></router-link>
  </div>
</template>

<script>
export default {
  name: 'Home'
}
</script>

<style scoped>
.home { padding: 20px; text-align: center; }
button { padding: 8px 14px; margin-top: 10px; cursor: pointer; }
</style>
