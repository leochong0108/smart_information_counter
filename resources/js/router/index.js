import { createRouter, createWebHashHistory } from 'vue-router';
import Home from '../views/public/Home.vue';
import Chat from '../views/public/Chat.vue';
import Login from '../views/private/login.vue'; // Import the new Login component
import AdminLayout from '../views/private/AdminLayout.vue'; // This will be the main container for private routes
import Intents from '../views/private/intents.vue';
import Faqs from '../views/private/faqs.vue';
import Departments from '../views/private/departments.vue';
import QuestionLogs from '../views/private/questionLog.vue';
import NotFoundComponent from '../components/NotFound.vue'; // A simple 404 component
import Register from '../views/private/Register.vue'; // If you create a register component
import FailLog from '../views/private/failLog.vue';
import dashboard from '../views/private/dashboard.vue';

const routes = [
  // Public routes that don't require authentication
  { path: '/', name: 'Home', component: Home },
  { path: '/chat', name: 'Chat', component: Chat },
  { path: '/login', name: 'Login', component: Login },
  { path: '/register', name: 'Register', component: Register },
  // You might also need a register route if you create a component for it
  // { path: '/register', name: 'Register', component: Register },

  // Protected routes that require authentication
  {
    path: '/admin',
    name: 'Admin',
    component: AdminLayout,
    meta: { requiresAuth: true }, // A meta field to mark this route and its children as protected
    children: [
      { path: 'intents', name: 'Intents', component: Intents },
      { path: 'faqs', name: 'Faqs', component: Faqs },
      { path: 'departments', name: 'Departments', component: Departments },
      { path: 'logs', name: 'QuestionLogs', component: QuestionLogs },
      { path: '', name: 'dashboard', component: dashboard    },
      { path: 'failLog', name: 'FailLog', component: FailLog    }
    ]
  },
  // Catch-all route for 404 Not Found
  { path: '/:pathMatch(.*)*', name: 'NotFound', component: NotFoundComponent } // You would need to create this component
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

// Navigation Guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('sanctum_token');
  if (to.meta.requiresAuth && !token) {
    // This route requires auth, check if logged in
    // if not, redirect to login page.
    next({ name: 'Login' });
  } else {
    next(); // otherwise, allow navigation
  }
});

export default router;
