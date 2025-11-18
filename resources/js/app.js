import './bootstrap';
import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';

// Add global headers to all Inertia requests (both initial and subsequent)
router.on('before', (event) => {
    const token = localStorage.getItem('authToken');
    if (token) {
        event.detail.visit.headers = {
            ...event.detail.visit.headers,
            'Authorization': `Bearer ${token}`,
        };
    }
});

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(Toast, {
                position: 'top-right',
                timeout: 5000,
                closeOnClick: true,
                pauseOnFocusLoss: true,
                pauseOnHover: true,
                draggable: true,
                draggablePercent: 0.6,
                showCloseButtonOnHover: false,
                hideProgressBar: false,
                closeButton: 'button',
                icon: true,
                rtl: false,
            })
            .mount(el);
    },
});
