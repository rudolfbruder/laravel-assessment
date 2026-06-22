import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Set token if available
const token = localStorage.getItem('token');
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Add a response interceptor for 401 Unauthorized
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

// Laravel Echo (Reverb) — private channels authenticated with the Sanctum
// Bearer token via a custom authorizer hitting /api/broadcasting/auth.
import { configureEcho, echo } from '@laravel/echo-vue';

configureEcho({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel) => ({
        authorize: (socketId, callback) => {
            const token = localStorage.getItem('token');
            if (!token) {
                callback(true, new Error('Unauthenticated'));
                return;
            }
            window.axios
                .post(
                    '/api/broadcasting/auth',
                    { socket_id: socketId, channel_name: channel.name },
                    { headers: { Authorization: `Bearer ${token}` } },
                )
                .then((response) => callback(false, response.data))
                .catch((error) => {
                    console.error('[reverb] channel auth failed for', channel.name, error?.response?.status);
                    callback(true, error);
                });
        },
    }),
});

// Diagnostics: expose Echo and log Reverb connection state.
try {
    const echoInstance = echo();
    window.Echo = echoInstance;
    const connection = echoInstance.connector?.pusher?.connection;
    if (connection) {
        connection.bind('state_change', (s) => console.log('[reverb] state', s.previous, '->', s.current));
        connection.bind('error', (e) => console.error('[reverb] connection error', e));
        // Log every raw frame (subscription acks + broadcast events).
        connection.bind('message', (m) => console.log('[reverb] frame', m));
    }
} catch (e) {
    console.error('[reverb] failed to initialize Echo', e);
}
