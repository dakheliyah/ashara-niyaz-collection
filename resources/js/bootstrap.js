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

// Determine if we're running on localhost
const isLocalhost = window.location.hostname === 'localhost';

console.log('=== AUTHENTICATION DEBUG INFO ===');
console.log('Current hostname:', window.location.hostname);
console.log('Is localhost:', isLocalhost);
console.log('Current URL:', window.location.href);

let token;

if (isLocalhost) {
    // Use hardcoded tokens for local development
    // token = 'PMVU4zSbtB%2FMFMCD2p%2BPwQ8rBUkc6TYQi1mNaTyoFpE%3D'; // Default to collector user
    // token = 'geFp5FFAagw7YvRYNDiREj%2BC5wY1RjQWm9K%2FDxxTTPo%3D'; // Default to admin user
    console.log('✅ Using hardcoded token for localhost development');
} else {
    // Get authentication token from its_no cookie for production
    console.log('🌐 Production environment detected');
    console.log('📋 All cookies:', document.cookie);
    
    // Try to get the token from cookie
    token = getCookie('its_no');
    console.log('🔑 Token from its_no cookie:', token ? `Found (${token.length} chars)` : 'NOT FOUND');
    
    if (!token) {
        console.error('❌ its_no cookie not found with primary method!');
        console.log('🔄 Trying fallback methods...');
        
        // Try regex-based parsing
        token = getCookieRegex('its_no');
        
        if (!token) {
            // Try alternative parsing method
            token = getCookieAlternative('its_no');
        }
        
        if (token) {
            console.log('✅ Token found using fallback method!');
        } else {
            console.error('❌ All cookie parsing methods failed!');
            console.log('Available cookies:', document.cookie);
            
            // Try alternative cookie names or methods
            const allCookies = document.cookie.split(';');
            console.log('All individual cookies:');
            allCookies.forEach(cookie => {
                const [name, value] = cookie.trim().split('=');
                console.log(`  - "${name}": ${value ? value.substring(0, 20) + '...' : 'empty'}`);
            });
            
            // Check for common variations of the cookie name
            const variations = ['its_no', 'its-no', 'itsno', 'ITS_NO', 'token', 'auth_token'];
            console.log('🔍 Checking cookie name variations:');
            variations.forEach(variation => {
                const testToken = getCookie(variation);
                if (testToken) {
                    console.log(`✅ Found token with variation "${variation}":`, testToken.substring(0, 20) + '...');
                    token = testToken; // Use the first found variation
                }
            });
        }
    }
}

if (token) {
    let finalToken;
    
    console.log('🔍 Token processing debug:');
    console.log('Raw token from cookie:', token);
    console.log('Token length:', token.length);
    console.log('Is localhost:', isLocalhost);
    
    if (isLocalhost) {
        // For localhost, use the token from cookie as-is (no hardcoding)
        finalToken = token;
        console.log('🔧 Using cookie token for localhost');
        
        // Try both URL-encoded and decoded versions to see which works
        console.log('🧪 Testing token variations:');
        console.log('Option 1 - Raw token:', token);
        console.log('Option 2 - URL encoded:', encodeURIComponent(token));
        console.log('Option 3 - URL decoded:', decodeURIComponent(token));
        
        // Since we're getting "Invalid token", let's try URL encoding it
        // The token might need to be URL-encoded for the API
        const encodedToken = encodeURIComponent(token);
        console.log('🔄 Trying URL-encoded version:', encodedToken);
        finalToken = encodedToken;
        
    } else {
        // Decode the URL-encoded token from cookie for production
        finalToken = decodeURIComponent(token);
        console.log('🔓 Decoded production token from cookie');
        console.log('Original token length:', token.length);
        console.log('Decoded token length:', finalToken.length);
    }
    
    // Set the token in axios defaults
    window.axios.defaults.headers.common['Token'] = finalToken;
    window.authToken = finalToken; // Make token available globally
    
    console.log('✅ Authentication token configured successfully');
    console.log('🔑 Final token being sent to API:', finalToken);
    console.log('📡 Axios headers configured:', Object.keys(window.axios.defaults.headers.common));
} else {
    console.error('❌ Authentication token not found. API requests will fail.');
    console.log('Environment:', isLocalhost ? 'localhost' : 'production');
    console.log('Available cookies:', document.cookie);
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
