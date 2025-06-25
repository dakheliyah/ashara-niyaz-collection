import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Hardcoded token for development. In a real app, you would get this after login.
// const token = 'PMVU4zSbtB%2FMFMCD2p%2BPwQ8rBUkc6TYQi1mNaTyoFpE%3D'; // Default to collector user
const token = 'geFp5FFAagw7YvRYNDiREj%2BC5wY1RjQWm9K%2FDxxTTPo%3D'; // Default to admin user

if (token) {
    window.axios.defaults.headers.common['Token'] = token;
} else {
    console.error('Authentication token is not set. API requests will fail.');
}
