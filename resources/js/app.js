import { initializeAuth } from './bootstrap';
import { createApp } from 'vue';
import App from './components/App.vue';
import router from './router';

// Initialize the application
async function startApp() {
    console.log('[App.js] Starting application...');
    console.log('[App.js] Awaiting authentication...');

    await initializeAuth();

    console.log('[App.js] Authentication finished. Mounting Vue app.');
    const app = createApp(App);
    app.use(router);
    app.mount('#app');
}

startApp();
