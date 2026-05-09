import axios from 'axios';
window.axios = axios;

// Configure axios headers
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

let isRedirectingToAuth = false;

function redirectToAuthRelay() {
    if (isRedirectingToAuth) {
        return;
    }

    const relayUrl = import.meta.env.VITE_AUTH_RELAY_URL;
    if (typeof relayUrl !== 'string' || relayUrl.trim().length === 0) {
        console.warn('VITE_AUTH_RELAY_URL is not configured; cannot redirect for token retrieval.');
        return;
    }

    try {
        const destination = new URL(relayUrl);
        isRedirectingToAuth = true;
        window.location.replace(destination.toString());
    } catch (e) {
        console.error('Invalid VITE_AUTH_RELAY_URL; cannot redirect for token retrieval.', e);
    }
}

// Function to get cookie value by name
function getCookie(name) {
    console.log('🍪 getCookie() called for:', name);
    console.log('🍪 Raw document.cookie:', document.cookie);
    
    if (!document.cookie) {
        console.log('❌ No cookies found in document.cookie');
        return null;
    }
    
    // Split cookies and trim whitespace
    const cookies = document.cookie.split(';');
    console.log('🍪 Split cookies array:', cookies);
    
    for (let cookie of cookies) {
        const trimmedCookie = cookie.trim();
        console.log('🍪 Processing cookie:', trimmedCookie);
        
        if (trimmedCookie.startsWith(name + '=')) {
            const value = trimmedCookie.substring(name.length + 1);
            console.log('✅ Found cookie value:', value);
            return value;
        }
    }
    
    console.log('❌ Cookie not found:', name);
    
    // Additional debugging - show all cookie names
    console.log('🍪 Available cookie names:');
    cookies.forEach(cookie => {
        const trimmed = cookie.trim();
        const equalIndex = trimmed.indexOf('=');
        if (equalIndex > 0) {
            const cookieName = trimmed.substring(0, equalIndex);
            console.log(`  - "${cookieName}"`);
        }
    });
    
    return null;
}

// Fallback cookie parsing function using regex
function getCookieRegex(name) {
    console.log('🔄 Trying regex-based cookie parsing for:', name);
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) {
            const value = c.substring(nameEQ.length, c.length);
            console.log('✅ Regex method found value:', value);
            return value;
        }
    }
    
    console.log('❌ Regex method failed');
    return null;
}

// Alternative cookie parsing using URLSearchParams-like approach
function getCookieAlternative(name) {
    console.log('🔄 Trying alternative cookie parsing for:', name);
    try {
        const cookies = document.cookie.split(';').reduce((acc, cookie) => {
            const [key, value] = cookie.trim().split('=');
            if (key && value) {
                acc[key] = value;
            }
            return acc;
        }, {});
        
        console.log('🍪 Parsed cookies object:', cookies);
        
        if (cookies[name]) {
            console.log('✅ Alternative method found value:', cookies[name]);
            return cookies[name];
        }
    } catch (error) {
        console.error('❌ Alternative parsing error:', error);
    }
    
    console.log('❌ Alternative method failed');
    return null;
}

// Export a function to initialize authentication. This allows us to control the timing.
export async function initializeAuth() {
    console.log('[Bootstrap.js] initializeAuth() called. Token setup is now handled by the request interceptor.');
    // This function is now just a placeholder to ensure app.js starts correctly.
    // All token logic has been moved to the axios request interceptor.
}

// Add request interceptor to log outgoing requests
window.axios.interceptors.request.use(
    function (config) {
        // Read the token from the cookie before every request
        const token = getCookie('its_no');
        
        // If a token exists, add it to the request headers
        if (token) {
            config.headers['Token'] = token;
        } else {
            // Ensure the header is removed if no token is found
            delete config.headers['Token'];
        }

        console.log('\n🚀 === OUTGOING API REQUEST ===');
        console.log('📍 URL:', config.url);
        console.log('🔧 Method:', config.method?.toUpperCase() || 'GET');
        console.log('📤 All Headers:', config.headers);
        console.log('🔑 Token Header:', config.headers['Token'] || 'NOT SET');
        console.log('🔑 Token Present:', !!config.headers['Token']);
        
        if (config.data) {
            console.log('📦 Request Data:', config.data);
        }
        
        if (config.params) {
            console.log('🔗 URL Params:', config.params);
        }
        
        console.log('⏰ Request Time:', new Date().toISOString());
        console.log('=== END REQUEST LOG ===\n');
        
        return config;
    },
    function (error) {
        console.error('\n❌ === REQUEST INTERCEPTOR ERROR ===');
        console.error('Error:', error);
        console.error('=== END REQUEST ERROR ===\n');
        return Promise.reject(error);
    }
);

// Add response interceptor to log responses
window.axios.interceptors.response.use(
    function (response) {
        console.log('\n✅ === API RESPONSE SUCCESS ===');
        console.log('📍 URL:', response.config.url);
        console.log('📥 Status:', response.status, response.statusText);
        console.log('📥 Response Headers:', response.headers);
        console.log('📦 Response Data:', response.data);
        console.log('⏰ Response Time:', new Date().toISOString());
        console.log('=== END RESPONSE LOG ===\n');
        
        return response;
    },
    function (error) {
        console.error('\n❌ === API RESPONSE ERROR ===');
        console.error('📍 URL:', error.config?.url || 'Unknown');
        console.error('🔧 Method:', error.config?.method?.toUpperCase() || 'Unknown');
        console.error('📥 Error Status:', error.response?.status || 'No Status');
        console.error('📥 Error Status Text:', error.response?.statusText || 'No Status Text');
        console.error('📥 Error Headers:', error.response?.headers || 'No Headers');
        console.error('📥 Error Data:', error.response?.data || 'No Data');
        console.error('📥 Full Error Message:', error.message);
        
        // Special handling for authentication errors
        if (error.response?.status === 401 || error.response?.data?.message?.includes('token')) {
            console.error('\n🔐 === AUTHENTICATION ERROR DETECTED ===');
            console.error('🔑 Token Sent:', error.config?.headers?.['Token'] || 'NO TOKEN SENT');
            console.error('🔑 Token Length:', error.config?.headers?.['Token']?.length || 0);
            console.error('🔑 Expected Token Format: Base64 encoded string');
            console.error('💡 Suggestion: Check if token needs URL encoding/decoding');
            console.error('=== END AUTH ERROR ===\n');
        }

        const errorMessage = String(error.response?.data?.message || '').toLowerCase();
        if (error.response?.status === 401 && errorMessage.includes('token header is required')) {
            redirectToAuthRelay();
        }
        
        console.error('⏰ Error Time:', new Date().toISOString());
        console.error('=== END ERROR LOG ===\n');
        
        return Promise.reject(error);
    }
);

console.log('=== END AUTHENTICATION DEBUG ===');
