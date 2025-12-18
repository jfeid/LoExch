import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Configure toastr
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    timeOut: 5000,
};
window.toastr = toastr;

// Initialize Pusher/Echo only if credentials are configured
if (import.meta.env.VITE_PUSHER_APP_KEY) {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: true,
    });
}
