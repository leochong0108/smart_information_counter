<!-- <template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-8 space-y-8 bg-white rounded-lg shadow-lg">
      <h2 class="text-3xl font-bold text-center text-gray-900">Simple Login</h2>

      <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email-address" class="sr-only">Email address</label>
            <input
              id="email-address"
              v-model="email"
              name="email"
              type="email"
              autocomplete="email"
              required
              class="relative block w-full px-3 py-2 text-gray-900 placeholder-gray-500 border border-gray-300 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Email address"
            />
          </div>
          <div>
            <label for="password" class="sr-only">Password</label>
            <input
              id="password"
              v-model="password"
              name="password"
              type="password"
              autocomplete="current-password"
              required
              class="relative block w-full px-3 py-2 text-gray-900 placeholder-gray-500 border border-gray-300 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Password"
            />
          </div>
        </div>

        <div v-if="error" class="text-sm text-red-500">
          {{ error }}
        </div>

        <div>
          <button
            type="submit"
            class="relative flex justify-center w-full px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md group hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Sign in
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Login',
  data() {
    return {
      email: '',
      password: '',
      error: '',
    };
  },
  methods: {
    async handleLogin() {
    console.log(this.email);
    console.log(this.password);
      this.error = '';
      try {
        await axios.post('/api/login', {
          email: this.email,
          password: this.password,
        });


        // The request was successful, redirect the user
        this.$router.push('/chat'); // Redirect to chat page on success
      } catch (err) {
        // The request failed, display an error message
        this.error = err.response?.data?.message || 'Invalid credentials. Please try again.';
      }
    },
  },
};
</script> -->




<template>
    <div class="loginform">
        <div class="card shadow login-card">
            <div class="card-body p-4 p-md-5">
                <h2 class="card-title text-center mb-4">Sign In</h2>
                <p class="text-center text-muted mb-4">
                    Or <router-link to="/register"><a class="text-decoration-none">create a new account</a></router-link>
                </p>

                <!-- Loading State -->
                <div v-if="loginStatus === 'loading'" class="text-center my-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Signing in...</p>
                </div>

                <!-- Success State -->
                <div v-else-if="loginStatus === 'success'" class="alert alert-success text-center" role="alert">
                    <h4 class="alert-heading">Login Successful!</h4>
                    <p>Welcome back! You are now authenticated.</p>
                    <hr>
                    <p class="mb-0 small">The application would now typically redirect you to the main dashboard.</p>
                    <button @click="resetForm" class="btn btn-sm btn-success mt-2">Try Again</button>
                </div>

                <!-- Login Form (Default State) -->
                <form v-else @submit.prevent="handleLogin" novalidate>
                    <div class="mb-3">
                        <label for="email-address" class="form-label visually-hidden">Email address</label>
                        <input
                            id="email-address"
                            v-model="email"
                            type="email"
                            class="form-control"
                            placeholder="Email address"
                            required
                        />
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label visually-hidden">Password</label>
                        <input
                            id="password"
                            v-model="password"
                            type="password"
                            class="form-control"
                            placeholder="Password"
                            required
                        />
                    </div>

                    <!-- Error Alert -->
                    <div v-if="error" class="alert alert-danger" role="alert">
                        {{ error }}
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            Sign in
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</template>

<script>
import axios from 'axios';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

export default {
    setup() {
        // State using ref()
        const email = ref('');
        const password = ref('');
        const error = ref('');
        const router = useRouter();
        const loginStatus = ref('form'); // 'form', 'loading', 'success'

        // Function to reset the form state
        const resetForm = () => {
            loginStatus.value = 'form';
            email.value = '';
            password.value = '';
            error.value = '';
        };

        // Function replacing methods
        const handleLogin = async () => {
            loginStatus.value = 'loading';
            error.value = '';

            // Using the provided API endpoint /api/login
            try {
                // Introduce a small delay for a better loading experience
                await new Promise(resolve => setTimeout(resolve, 500));

                // Real API call
                const response = await axios.post('/api/login', {
                    email: email.value,
                    password: password.value,
                });

                // Assuming the response contains a token in response.data.token
                const token = response.data.token;

                if (token) {
                    // Store token and show success
                    localStorage.setItem('sanctum_token', token);
                    loginStatus.value = 'success';

                    router.push('/admin');

                } else {
                     // Handle case where API returns 200 but missing expected token
                    throw new Error("Authentication token missing in server response.");
                }


            } catch (err) {
                // Handle the error and revert to the form state
                loginStatus.value = 'form';

                if (err.response) {
                    // 1. Server responded with a status outside of 2xx (e.g., 401 Unauthorized)
                    // The error message might be in err.response.data or err.response.data.message
                    error.value = err.response.data.message || err.response.data.error || 'Login failed. Invalid credentials.';
                } else if (err.request) {
                    // 2. The request was made but no response was received (e.g., network timeout, CORS issues)
                    error.value = 'Network Error: Could not reach the authentication server.';
                } else {
                    // 3. Something else happened (e.g., an error in the JavaScript logic, like missing token)
                    error.value = err.message || 'An unexpected error occurred during login.';
                }
                console.error('Login failed:', err);
            }
        };

        // Return all reactive state and functions for use in the template
        return {
            email,
            password,
            error,
            loginStatus,
            handleLogin,
            resetForm
        };
    }
};

</script>

<style>
.loginform {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
}

        .login-card {
            width: 100%;
            max-width: 400px;
        }
</style>
