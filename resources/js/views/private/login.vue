<template>
    <div class="auth-layout d-flex justify-content-center align-items-center">
        <!-- 背景装饰 -->
        <div class="bg-circle circle-1"></div>
        <div class="bg-circle circle-2"></div>

        <div class="card auth-card shadow-lg border-0 p-4 animate-fade-up">
            <div class="card-body">

                <!-- Logo / Header -->
                <div class="text-center mb-4">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary mx-auto mb-3">
                        <i class="bi bi-person-circle fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Welcome Back</h3>
                    <p class="text-muted small">Please sign in to your account</p>
                </div>

                <!-- Error Alert -->
                <div v-if="error" class="alert alert-danger d-flex align-items-center p-2 mb-3 small" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <div>{{ error }}</div>
                </div>

                <!-- Success Alert -->
                <div v-if="loginStatus === 'success'" class="alert alert-success p-2 mb-3 small text-center">
                    <i class="bi bi-check-circle-fill me-1"></i> Login successful! Redirecting...
                </div>

                <form @submit.prevent="handleLogin" novalidate>
                    <!-- Email -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                            <input
                                v-model="email"
                                type="email"
                                class="form-control bg-light border-start-0"
                                placeholder="name@example.com"
                                required
                                :disabled="isLoading"
                            />
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                v-model="password"
                                class="form-control bg-light border-start-0 border-end-0"
                                placeholder="Enter password"
                                required
                                :disabled="isLoading"
                            />
                            <button class="btn btn-light border border-start-0 text-muted" type="button" @click="showPassword = !showPassword">
                                <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm" :disabled="isLoading">
                            <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            {{ isLoading ? 'Signing in...' : 'Sign In' }}
                        </button>
                    </div>
                </form>

                <!-- Footer Links -->
                <div class="text-center mt-4 pt-3 border-top">
                    <p class="small text-muted mb-2">
                        Don't have an account?
                        <router-link to="/register" class="text-primary text-decoration-none fw-bold">Sign Up</router-link>
                    </p>
                    <router-link to="/" class="small text-secondary text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Back to Home
                    </router-link>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

// State
const email = ref('');
const password = ref('');
const error = ref('');
const loginStatus = ref('idle'); // 'idle', 'loading', 'success'
const showPassword = ref(false); // Toggle password visibility

// Computed for cleaner template
const isLoading = computed(() => loginStatus.value === 'loading');

// Import computed if not auto-imported
import { computed } from 'vue';

const handleLogin = async () => {
    // Basic validation
    if (!email.value || !password.value) {
        error.value = "Please fill in all fields.";
        return;
    }

    loginStatus.value = 'loading';
    error.value = '';

    try {
        // Fake delay for UX (optional, can be removed)
        await new Promise(resolve => setTimeout(resolve, 600));

        const response = await axios.post('/api/login', {
            email: email.value,
            password: password.value,
        });

        const token = response.data.token;

        if (token) {
            localStorage.setItem('sanctum_token', token);
            loginStatus.value = 'success';
            // Redirect after a short delay to show success message
            setTimeout(() => {
                router.push('/admin');
            }, 500);
        } else {
            throw new Error("Token missing.");
        }

    } catch (err) {
        loginStatus.value = 'idle';
        if (err.response) {
            error.value = err.response.data.message || 'Invalid credentials.';
        } else {
            error.value = 'Network error. Please try again.';
        }
    }
};
</script>

<style scoped>
/* 全局布局 & 背景 (与 Home.vue 风格一致) */
.auth-layout {
    min-height: 100vh;
    background-color: #f0f2f5;
    position: relative;
    overflow: hidden;
    padding: 15px;
}

.auth-card {
    width: 100%;
    max-width: 400px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    z-index: 10;
}

/* 图标样式 */
.icon-box {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 背景圆球 */
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

/* 动画 */
.animate-fade-up {
    animation: fadeUp 0.6s ease-out;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* 手机端适配 */
@media (max-width: 576px) {
    .bg-circle { opacity: 0.3; }
}
</style>
