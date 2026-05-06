import { initializeAuth } from './bootstrap';
import { createApp } from 'vue';
import App from './components/App.vue';
import router from './router';

// WordPress OneLogin partner handoff: must hit Laravel (not SPA-only) to exchange token server-side.
function redirectOneLoginHandoffIfNeeded() {
    const url = new URL(window.location.href);
    if (!url.searchParams.get('onlgn_token')) {
        return false;
    }
    const params = new URLSearchParams(url.searchParams);
    window.location.replace('/auth/onlgn-handoff?' + params.toString());
    return true;
}

// Initialize the application
async function startApp() {
    if (redirectOneLoginHandoffIfNeeded()) {
        return;
    }

    console.log('[App.js] Starting application...');
    console.log('[App.js] Awaiting authentication...');

    await initializeAuth();

    console.log('[App.js] Authentication finished. Mounting Vue app.');
    const app = createApp(App);
    app.use(router);
    app.mount('#app');
}

startApp();
