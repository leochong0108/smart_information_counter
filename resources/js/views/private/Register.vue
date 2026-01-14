<template>
    <div class="auth-layout d-flex justify-content-center align-items-center">
        <div class="bg-circle circle-1"></div>
        <div class="bg-circle circle-2"></div>

        <div class="card auth-card shadow-lg border-0 p-4 animate-fade-up">
            <div class="card-body">

                <div class="text-center mb-4">
                    <div class="icon-box bg-success bg-opacity-10 text-success mx-auto mb-3">
                        <i class="bi bi-person-plus-fill fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Create Account</h3>
                    <p class="text-muted small">Join us to manage the chatbot</p>
                </div>

                <div v-if="error" class="alert alert-danger d-flex align-items-center p-2 mb-3 small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>{{ error }}</div>
                </div>

                <div v-if="registerStatus === 'success'" class="alert alert-success p-3 text-center">
                    <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-check-circle-fill"></i> Success!</h6>
                    <p class="small mb-2">Your account has been created.</p>
                    <router-link to="/login" class="btn btn-sm btn-success w-100">Go to Login</router-link>
                </div>

                <form v-else @submit.prevent="handleRegister" novalidate>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                            <input
                                v-model="name"
                                type="text"
                                class="form-control bg-light border-start-0"
                                placeholder="Your Name"
                                required
                                :disabled="isLoading"
                            />
                        </div>
                    </div>

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

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                v-model="password"
                                class="form-control bg-light border-start-0 border-end-0"
                                placeholder="Create a password"
                                required
                                :disabled="isLoading"
                            />
                            <button class="btn btn-light border border-start-0 text-muted" type="button" @click="showPassword = !showPassword">
                                <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </div>
                        <div class="form-text small" v-if="password && password.length < 6">
                            Must be at least 6 characters.
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm" :disabled="isLoading">
                            <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                            {{ isLoading ? 'Creating...' : 'Sign Up' }}
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="small text-muted mb-2">
                        Already have an account?
                        <router-link to="/login" class="text-primary text-decoration-none fw-bold">Sign In</router-link>
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
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

const name = ref('');
const email = ref('');
const password = ref('');
const error = ref('');
const registerStatus = ref('idle');
const showPassword = ref(false);

const isLoading = computed(() => registerStatus.value === 'loading');

const handleRegister = async () => {

    if (!name.value || !email.value || !password.value) {
        error.value = "Please fill in all fields.";
        return;
    }
    if (password.value.length < 6) {
        error.value = "Password must be at least 6 characters.";
        return;
    }

    registerStatus.value = 'loading';
    error.value = '';

    try {
        await new Promise(resolve => setTimeout(resolve, 600));

        await axios.post('/api/register', {
            name: name.value,
            email: email.value,
            password: password.value,
        });

        registerStatus.value = 'success';

    } catch (err) {
        registerStatus.value = 'idle';
        error.value = err.response?.data?.message || 'Registration failed.';
    }
};
</script>

<style scoped>
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

.icon-box {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
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

.animate-fade-up {
    animation: fadeUp 0.6s ease-out;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 576px) {
    .bg-circle { opacity: 0.3; }
}
</style>
