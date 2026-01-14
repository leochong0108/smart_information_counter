<template>
  <div class="home-layout d-flex flex-column justify-content-center align-items-center">

    <div class="bg-circle circle-1"></div>
    <div class="bg-circle circle-2"></div>

    <div class="home-card shadow-lg p-4 p-md-5 text-center animate-fade-up">


      <div class="logo-container mb-4 mx-auto">
        <i class="bi bi-robot fs-1 text-primary"></i>
      </div>


      <h1 class="fw-bold text-dark mb-2 title-text">Smart Information Counter</h1>
      <p class="text-muted mb-4 description-text">
        Welcome to Southern University College.<br>
        Click the button below to start a conversation with our AI assistant and get the information you need.
      </p>


      <div class="d-grid gap-3">

        <router-link to="/chat" class="btn btn-primary btn-lg rounded-pill py-3 shadow-sm btn-start">
          <i class="bi bi-chat-dots-fill me-2"></i> Start Chatting
        </router-link>

        <router-link to="/admin" class="btn btn-outline-secondary btn-sm rounded-pill py-2 mt-2">
          <i class="bi bi-shield-lock me-1"></i> Admin Login
        </router-link>
      </div>

    </div>

    <footer class="mt-4 text-muted small position-relative z-10">
      &copy; {{ new Date().getFullYear() }} Smart Information Counter. All rights reserved.
    </footer>

  </div>
</template>

<script>
export default {
  name: 'Home'
}
</script>

<style scoped>

.home-layout {
  min-height: 100dvh;
  background-color: #f0f2f5;
  position: relative;
  overflow: hidden;
  padding: 20px;
}


.home-card {
  width: 100%;
  max-width: 480px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 24px;
  position: relative;
  z-index: 10;
}


.logo-container {
  width: 80px;
  height: 80px;
  background: #e3f2fd;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: float 3s ease-in-out infinite;
}


.title-text {
  font-size: 1.75rem;
  letter-spacing: -0.5px;
}
.description-text {
  font-size: 1rem;
  line-height: 1.6;
}


.btn-start {
  transition: transform 0.2s, box-shadow 0.2s;
  font-weight: 600;
}
.btn-start:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(13, 110, 253, 0.3) !important;
}


.bg-circle {
  position: absolute;
  border-radius: 50%;
  z-index: 0;
  opacity: 0.6;
}
.circle-1 {
  width: 300px;
  height: 300px;
  background: linear-gradient(135deg, #a2c2e8 0%, #f0f2f5 100%);
  top: -50px;
  right: -50px;
}
.circle-2 {
  width: 200px;
  height: 200px;
  background: linear-gradient(135deg, #e3f2fd 0%, #dbeafe 100%);
  bottom: -20px;
  left: -20px;
}


@keyframes fade-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-up {
  animation: fade-up 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}

@keyframes float {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
  100% { transform: translateY(0px); }
}


@media (min-width: 768px) {
  .title-text {
    font-size: 2rem;
  }
  .home-card {
    padding: 3rem !important;
  }
}


@media (max-width: 576px) {
  .home-card {
    border-radius: 20px;
    background: #ffffff;
  }
  .circle-1, .circle-2 {
    opacity: 0.4;
  }
}
</style>
