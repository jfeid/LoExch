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

// Global OrderMatched listener for toastr notifications
function setupOrderMatchedListener() {
    const userId = document.querySelector('meta[name="user-id"]')?.content;
    if (userId && window.Echo) {
        window.Echo.private(`user.${userId}`)
            .listen('.OrderMatched', (event) => {
                const trade = event.trade;
                const isBuyer = trade.buyer_id == userId;
                const action = isBuyer ? 'bought' : 'sold';
                const message = `Order matched: You ${action} ${trade.amount} ${trade.symbol} @ $${parseFloat(trade.price).toLocaleString()}`;
                window.toastr.success(message, 'Trade Executed');
            });
    }
}

// Setup listener on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupOrderMatchedListener);
} else {
    setupOrderMatchedListener();
}

// Re-setup after Livewire SPA navigation
document.addEventListener('livewire:navigated', setupOrderMatchedListener);
