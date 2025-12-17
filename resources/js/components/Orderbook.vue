<template>
    <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Orderbook</h3>
            <select v-model="selectedSymbol" class="rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-sm">
                <option value="BTC">BTC</option>
                <option value="ETH">ETH</option>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <!-- Buy Orders -->
            <div>
                <h4 class="text-sm font-medium text-green-600 mb-2">Buy Orders</h4>
                <div class="space-y-1 max-h-64 overflow-y-auto">
                    <div v-for="order in buyOrders" :key="order.id"
                        class="flex justify-between text-xs bg-green-50 dark:bg-green-900/20 p-2 rounded">
                        <span class="text-green-700 dark:text-green-400">{{ formatNumber(order.price) }}</span>
                        <span class="text-zinc-600 dark:text-zinc-400">{{ formatNumber(order.amount) }}</span>
                    </div>
                    <div v-if="buyOrders.length === 0" class="text-xs text-zinc-400 text-center py-4">
                        No buy orders
                    </div>
                </div>
            </div>

            <!-- Sell Orders -->
            <div>
                <h4 class="text-sm font-medium text-red-600 mb-2">Sell Orders</h4>
                <div class="space-y-1 max-h-64 overflow-y-auto">
                    <div v-for="order in sellOrders" :key="order.id"
                        class="flex justify-between text-xs bg-red-50 dark:bg-red-900/20 p-2 rounded">
                        <span class="text-red-700 dark:text-red-400">{{ formatNumber(order.price) }}</span>
                        <span class="text-zinc-600 dark:text-zinc-400">{{ formatNumber(order.amount) }}</span>
                    </div>
                    <div v-if="sellOrders.length === 0" class="text-xs text-zinc-400 text-center py-4">
                        No sell orders
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';

const props = defineProps({
    initialSymbol: { type: String, default: 'BTC' }
});

const selectedSymbol = ref(props.initialSymbol);
const orders = ref([]);
const loading = ref(false);

const buyOrders = computed(() => {
    return orders.value
        .filter(o => o.side === 'buy')
        .sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
});

const sellOrders = computed(() => {
    return orders.value
        .filter(o => o.side === 'sell')
        .sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 }).format(num);
};

const fetchOrders = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/api/orders?symbol=${selectedSymbol.value}`, {
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

watch(selectedSymbol, fetchOrders);

onMounted(() => {
    fetchOrders();

    // Listen for real-time updates
    if (window.Echo) {
        window.Echo.channel(`orderbook.${selectedSymbol.value}`)
            .listen('OrderCreated', (e) => {
                if (e.order.symbol === selectedSymbol.value) {
                    orders.value.push(e.order);
                }
            })
            .listen('OrderCancelled', (e) => {
                orders.value = orders.value.filter(o => o.id !== e.order.id);
            });
    }
});
</script>
