import { createRouter, createWebHistory } from 'vue-router';
import RegistrationView from './views/RegistrationView.vue';
import PayView from './views/PayView.vue';
import ReturnView from './views/ReturnView.vue';

export default createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', component: RegistrationView },
    { path: '/pay/:token', component: PayView, props: true },
    { path: '/return', component: ReturnView },
  ],
});
