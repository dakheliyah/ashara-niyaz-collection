import axios from 'axios';
window.axios = axios;

// Configure axios headers
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Function to get cookie value by name
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Determine if we're running on localhost
const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

let token;

if (isLocalhost) {
    // Use hardcoded tokens for local development
    // const token = 'PMVU4zSbtB%2FMFMCD2p%2BPwQ8rBUkc6TYQi1mNaTyoFpE%3D'; // Default to collector user
    token = 'geFp5FFAagw7YvRYNDiREj%2BC5wY1RjQWm9K%2FDxxTTPo%3D'; // Default to admin user
    console.log('Using hardcoded token for localhost development');
} else {
    // Get authentication token from its_no cookie for production
    token = getCookie('its_no');
    console.log('Using token from its_no cookie for production');
}

if (token) {
    let finalToken;
    
    if (isLocalhost) {
        // Use hardcoded token as-is for localhost (it's already URL-encoded)
        finalToken = token;
    } else {
        // Decode the URL-encoded token from cookie for production
        finalToken = decodeURIComponent(token);
    }
    
    window.axios.defaults.headers.common['Token'] = finalToken;
    window.authToken = finalToken; // Make token available globally
    console.log('Authentication token configured successfully');
    console.log('Token being used:', finalToken);
} else {
    console.error('Authentication token not found. API requests will fail.');
}
