<template>
  <div class="home">
    <h1>Smart Information Counter</h1>
    <p>Welcome. Click to start chatting.</p>
    <router-link to="/chat"><button>Open Chat</button></router-link>
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
