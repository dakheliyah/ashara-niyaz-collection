import axios from 'axios';
window.axios = axios;

// Configure axios headers
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

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
    console.log('[Bootstrap.js] Initializing authentication...');
    let token;

    const hostname = window.location.hostname;
    if (hostname === 'localhost' || hostname === '127.0.0.1') {
        console.log('[Bootstrap.js] Running on localhost. Using hardcoded admin token for development.');
        token = 'geFp5FFAagw7YvRYNDiREj%2BC5wY1RjQWm9K%2FDxxTTPo%3D';
    } else {
        console.log('[Bootstrap.js] Running on production. Reading token from its_no cookie.');
        token = getCookie('its_no');
        console.log(`[Bootstrap.js] Token from 'its_no' cookie:`, token);
    }

    if (token) {
        console.log('[Bootstrap.js] Token acquired. Setting axios header.');
        window.axios.defaults.headers.common['Token'] = token;
        window.authToken = token; // Also store it globally for access
    } else {
        console.log("[Bootstrap.js] No token found. Deleting token header.");
        delete window.axios.defaults.headers.common['Token'];
        window.authToken = null;
    }
    
    console.log('[Bootstrap.js] Axios headers after init:', window.axios.defaults.headers.common);
    console.log('[Bootstrap.js] Auth token is now globally available as window.authToken:', window.authToken);
}

// Add request interceptor to log outgoing requests
window.axios.interceptors.request.use(
    function (config) {
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
        
        console.error('⏰ Error Time:', new Date().toISOString());
        console.error('=== END ERROR LOG ===\n');
        
        return Promise.reject(error);
    }
);

console.log('=== END AUTHENTICATION DEBUG ===');
