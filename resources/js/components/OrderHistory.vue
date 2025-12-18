<template>
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">My Orders</h3>
        </div>

        <div class="p-5">
            <div v-if="loading" class="text-zinc-400 dark:text-zinc-500 text-sm py-8 text-center">Loading...</div>

            <div v-else>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left border-b-2 border-zinc-200 dark:border-zinc-700">
                                <th class="pb-3 font-semibold text-zinc-600 dark:text-zinc-400">Symbol</th>
                                <th class="pb-3 font-semibold text-zinc-600 dark:text-zinc-400">Side</th>
                                <th class="pb-3 font-semibold text-zinc-600 dark:text-zinc-400">Price</th>
                                <th class="pb-3 font-semibold text-zinc-600 dark:text-zinc-400">Amount</th>
                                <th class="pb-3 font-semibold text-zinc-600 dark:text-zinc-400">Status</th>
                                <th class="pb-3 font-semibold text-zinc-600 dark:text-zinc-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="order in orders" :key="order.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="py-4 font-semibold text-zinc-900 dark:text-zinc-100">{{ order.symbol }}</td>
                                <td class="py-4">
                                    <span :class="[
                                        order.side === 'buy'
                                            ? 'text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 border-emerald-200 dark:border-emerald-800'
                                            : 'text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/30 border-rose-200 dark:border-rose-800',
                                        'px-2.5 py-1 rounded-md text-xs font-bold border'
                                    ]">
                                        {{ order.side.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="py-4 font-medium text-zinc-900 dark:text-zinc-100">${{ formatNumber(order.price) }}</td>
                                <td class="py-4 font-medium text-zinc-900 dark:text-zinc-100">{{ formatNumber(order.amount) }}</td>
                                <td class="py-4">
                                    <span :class="statusClass(order.status)">{{ order.status }}</span>
                                </td>
                                <td class="py-4">
                                    <button v-if="order.status === 'open'" @click="cancelOrder(order)"
                                        :disabled="cancelling === order.id"
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 disabled:opacity-50 transition-colors">
                                        {{ cancelling === order.id ? 'Cancelling...' : 'Cancel' }}
                                    </button>
                                    <span v-else class="text-xs text-zinc-400">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="orders.length === 0"
                    class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-12 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700">
                    No orders yet
                </div>
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
        'open': 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 px-2.5 py-1 rounded-md text-xs font-bold',
        'filled': 'text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 px-2.5 py-1 rounded-md text-xs font-bold',
        'cancelled': 'text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 px-2.5 py-1 rounded-md text-xs font-bold'
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

        const data = await response.json();

        if (response.ok) {
            const idx = orders.value.findIndex(o => o.id === order.id);
            if (idx !== -1) {
                orders.value[idx].status = 'cancelled';
            }
            window.toastr.success(
                `Order cancelled: ${order.side.toUpperCase()} ${order.amount} ${order.symbol} @ $${parseFloat(order.price).toLocaleString()}`,
                'Order Cancelled'
            );
        } else {
            window.toastr.error(data.message || 'Failed to cancel order', 'Cancel Failed');
        }
    } catch (e) {
        console.error('Failed to cancel order:', e);
        window.toastr.error('Failed to cancel order', 'Error');
    } finally {
        cancelling.value = null;
    }
};

onMounted(() => {
    fetchOrders();

    // Listen for real-time updates via Pusher
    if (window.Echo) {
        const userId = document.querySelector('meta[name="user-id"]')?.content;
        if (userId) {
            window.Echo.private(`user.${userId}`)
                .listen('.OrderCreated', () => {
                    fetchOrders();
                })
                .listen('.OrderCancelled', () => {
                    fetchOrders();
                })
                .listen('.OrderMatched', () => {
                    fetchOrders();
                });
        }
    }
});

// Expose refresh method
defineExpose({ refresh: fetchOrders });
</script>
