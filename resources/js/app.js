import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import VueSweetalert2 from 'vue-sweetalert2';
import 'bootstrap-icons/font/bootstrap-icons.css';

const app = createApp(App)
app.use(router)
app.use(VueSweetalert2);
app.mount('#app')
