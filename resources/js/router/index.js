import { createRouter, createWebHashHistory } from 'vue-router';
import Home from '../views/public/Home.vue';
import Chat from '../views/public/Chat.vue';
import Login from '../views/private/Login.vue';
import AdminLayout from '../views/private/AdminLayout.vue';
import Intents from '../views/private/Intents.vue';
import Faqs from '../views/private/Faqs.vue';
import Departments from '../views/private/Departments.vue';
import QuestionLogs from '../views/private/QuestionLog.vue';
import NotFoundComponent from '../components/NotFound.vue';
import Register from '../views/private/Register.vue';
import FailLog from '../views/private/FailLog.vue';
import dashboard from '../views/private/Dashboard.vue';

const routes = [

  { path: '/', name: 'Home', component: Home },
  { path: '/chat', name: 'Chat', component: Chat },
  { path: '/login', name: 'Login', component: Login },
  { path: '/register', name: 'Register', component: Register },

  {
    path: '/admin',
    name: 'Admin',
    component: AdminLayout,
    meta: { requiresAuth: true },
    children: [
      { path: 'intents', name: 'Intents', component: Intents },
      { path: 'faqs', name: 'Faqs', component: Faqs },
      { path: 'departments', name: 'Departments', component: Departments },
      { path: 'logs', name: 'QuestionLogs', component: QuestionLogs },
      { path: '', name: 'dashboard', component: dashboard    },
      { path: 'failLog', name: 'FailLog', component: FailLog    }
    ]
  },
  { path: '/:pathMatch(.*)*', name: 'NotFound', component: NotFoundComponent }
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('sanctum_token');
  if (to.meta.requiresAuth && !token) {

    next({ name: 'Login' });
  } else {
    next();
  }
});

export default router;
