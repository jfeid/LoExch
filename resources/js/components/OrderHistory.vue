<template>
    <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow">
        <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-white">My Orders</h3>

        <div v-if="loading" class="text-zinc-400 text-sm">Loading...</div>

        <div v-else>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-zinc-500 dark:text-zinc-400 border-b dark:border-zinc-700">
                            <th class="pb-2 font-medium">Symbol</th>
                            <th class="pb-2 font-medium">Side</th>
                            <th class="pb-2 font-medium">Price</th>
                            <th class="pb-2 font-medium">Amount</th>
                            <th class="pb-2 font-medium">Status</th>
                            <th class="pb-2 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        <tr v-for="order in orders" :key="order.id">
                            <td class="py-2 font-medium text-zinc-900 dark:text-white">{{ order.symbol }}</td>
                            <td class="py-2">
                                <span :class="order.side === 'buy' ? 'text-green-600' : 'text-red-600'">
                                    {{ order.side.toUpperCase() }}
                                </span>
                            </td>
                            <td class="py-2 text-zinc-900 dark:text-white">{{ formatNumber(order.price) }}</td>
                            <td class="py-2 text-zinc-900 dark:text-white">{{ formatNumber(order.amount) }}</td>
                            <td class="py-2">
                                <span :class="statusClass(order.status)">{{ order.status }}</span>
                            </td>
                            <td class="py-2">
                                <button v-if="order.status === 'open'" @click="cancelOrder(order)"
                                    :disabled="cancelling === order.id"
                                    class="text-xs text-red-600 hover:text-red-800 disabled:opacity-50">
                                    {{ cancelling === order.id ? 'Cancelling...' : 'Cancel' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="orders.length === 0" class="text-sm text-zinc-400 text-center py-4">
                No orders yet
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    csrfToken: { type: String, required: true }
});

const orders = ref([]);
const loading = ref(true);
const cancelling = ref(null);

const formatNumber = (num) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 }).format(num);
};

const statusClass = (status) => {
    return {
        'open': 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded text-xs',
        'filled': 'text-green-600 bg-green-50 dark:bg-green-900/20 px-2 py-0.5 rounded text-xs',
        'cancelled': 'text-zinc-500 bg-zinc-100 dark:bg-zinc-700 px-2 py-0.5 rounded text-xs'
    }[status] || 'text-zinc-500';
};

const fetchOrders = async () => {
    try {
        const response = await fetch('/api/user/orders', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });
        const data = await response.json();
        orders.value = data.data || [];
    } catch (e) {
        console.error('Failed to fetch orders:', e);
    } finally {
        loading.value = false;
    }
};

const cancelOrder = async (order) => {
    cancelling.value = order.id;
    try {
        const response = await fetch(`/api/orders/${order.id}/cancel`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            },
            credentials: 'same-origin'
        });

        if (response.ok) {
            const idx = orders.value.findIndex(o => o.id === order.id);
            if (idx !== -1) {
                orders.value[idx].status = 'cancelled';
            }
        }
    } catch (e) {
        console.error('Failed to cancel order:', e);
    } finally {
        cancelling.value = null;
    }
};

onMounted(() => {
    fetchOrders();

    // Listen for real-time updates
    if (window.Echo) {
        const userId = document.querySelector('meta[name="user-id"]')?.content;
        if (userId) {
            window.Echo.private(`user.${userId}`)
                .listen('OrderMatched', () => {
                    fetchOrders();
                });
        }
    }
});

// Expose refresh method
defineExpose({ refresh: fetchOrders });
</script>
