<template>
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-center">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Orderbook</h3>
            <select v-model="selectedSymbol"
                class="px-3 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm font-medium focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                <option value="BTC">BTC</option>
                <option value="ETH">ETH</option>
            </select>
        </div>

        <div class="p-5">
            <div class="grid grid-cols-2 gap-6">
                <!-- Buy Orders -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        <h4 class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Buy Orders</h4>
                    </div>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <div v-for="order in buyOrders" :key="order.id"
                            class="flex justify-between text-sm bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-lg border border-emerald-100 dark:border-emerald-900/30">
                            <span class="font-semibold text-emerald-700 dark:text-emerald-400">${{ formatNumber(order.price) }}</span>
                            <span class="text-zinc-600 dark:text-zinc-400 font-medium">{{ formatNumber(order.amount) }}</span>
                        </div>
                        <div v-if="buyOrders.length === 0" class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-8 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700">
                            No buy orders
                        </div>
                    </div>
                </div>

                <!-- Sell Orders -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                        <h4 class="text-sm font-semibold text-rose-600 dark:text-rose-400">Sell Orders</h4>
                    </div>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <div v-for="order in sellOrders" :key="order.id"
                            class="flex justify-between text-sm bg-rose-50 dark:bg-rose-900/20 p-3 rounded-lg border border-rose-100 dark:border-rose-900/30">
                            <span class="font-semibold text-rose-700 dark:text-rose-400">${{ formatNumber(order.price) }}</span>
                            <span class="text-zinc-600 dark:text-zinc-400 font-medium">{{ formatNumber(order.amount) }}</span>
                        </div>
                        <div v-if="sellOrders.length === 0" class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-8 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700">
                            No sell orders
                        </div>
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
