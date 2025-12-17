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

// Vue island mounting system
function mountVueIslands() {
    document.querySelectorAll('[data-vue]').forEach(el => {
        const componentName = el.dataset.vue;
        const props = el.dataset.props ? JSON.parse(el.dataset.props) : {};

        if (components[componentName]) {
            createApp({
                render() {
                    return h(components[componentName], props);
                }
            }).mount(el);
        }
    });
}

// Mount on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountVueIslands);
} else {
    mountVueIslands();
}

// Also expose for manual mounting
window.mountVueIslands = () => mountVueIslands();
