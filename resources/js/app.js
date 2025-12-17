import './bootstrap';
import { createApp, h } from 'vue';

// Vue island mounting system
// Components will be registered and mounted in Phase 8
window.mountVueIslands = function(components) {
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
};
