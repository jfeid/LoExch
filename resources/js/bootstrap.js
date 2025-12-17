import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Initialize Pusher/Echo only if credentials are configured
if (import.meta.env.VITE_PUSHER_APP_KEY) {
    import('pusher-js').then((Pusher) => {
        import('laravel-echo').then((Echo) => {
            window.Pusher = Pusher.default;
            window.Echo = new Echo.default({
                broadcaster: 'pusher',
                key: import.meta.env.VITE_PUSHER_APP_KEY,
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
                forceTLS: true,
            });
        });
    });
}
