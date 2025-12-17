import './bootstrap';
import { createApp, h } from 'vue';

// Import Vue components
import OrderForm from './components/OrderForm.vue';
import Orderbook from './components/Orderbook.vue';
import WalletOverview from './components/WalletOverview.vue';
import OrderHistory from './components/OrderHistory.vue';

// Register components
const components = {
    OrderForm,
    Orderbook,
    WalletOverview,
    OrderHistory
};

// Track mounted Vue app instances for cleanup
const mountedApps = new WeakMap();

// Vue island mounting system
function mountVueIslands() {
    document.querySelectorAll('[data-vue]').forEach(el => {
        // Skip if already mounted
        if (mountedApps.has(el)) {
            return;
        }

        const componentName = el.dataset.vue;
        const props = el.dataset.props ? JSON.parse(el.dataset.props) : {};

        if (components[componentName]) {
            const app = createApp({
                render() {
                    return h(components[componentName], props);
                }
            });
            app.mount(el);
            mountedApps.set(el, app);
        }
    });
}

// Mount on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountVueIslands);
} else {
    mountVueIslands();
}

// Re-mount after Livewire SPA navigation
document.addEventListener('livewire:navigated', mountVueIslands);

// Also expose for manual mounting
window.mountVueIslands = () => mountVueIslands();
