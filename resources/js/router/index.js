import { createRouter, createWebHashHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Chat from '../views/Chat.vue'

// Using hash history avoids needing server fallback; change to createWebHistory + Laravel fallback if desired.
const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/chat', name: 'Chat', component: Chat },

]

const router = createRouter({
  history: createWebHashHistory(), // or createWebHistory()
  routes,
})

export default router
