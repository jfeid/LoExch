<template>
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-center">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Orderbook</h3>
            <select v-model="selectedSymbol"
                class="px-3 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm font-medium focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                <option value="">All</option>
                <option value="BTC">BTC</option>
                <option value="ETH">ETH</option>
            </select>
        </div>

        <div class="p-5">
            <div class="grid grid-cols-2 gap-4">
                <!-- Buy Orders -->
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></div>
                        <h4 class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Buy Orders</h4>
                    </div>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <div v-for="order in buyOrders" :key="order.id"
                            class="bg-emerald-50 dark:bg-emerald-900/20 px-3 py-2.5 rounded-lg border border-emerald-100 dark:border-emerald-900/30">
                            <div class="text-base font-bold text-emerald-700 dark:text-emerald-400">${{ formatPrice(order.price) }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ formatAmount(order.amount) }} {{ order.symbol }}</div>
                        </div>
                        <div v-if="buyOrders.length === 0" class="text-xs text-zinc-400 dark:text-zinc-500 text-center py-6 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700">
                            No buy orders
                        </div>
                    </div>
                </div>

                <!-- Sell Orders -->
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></div>
                        <h4 class="text-sm font-semibold text-rose-600 dark:text-rose-400">Sell Orders</h4>
                    </div>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <div v-for="order in sellOrders" :key="order.id"
                            class="bg-rose-50 dark:bg-rose-900/20 px-3 py-2.5 rounded-lg border border-rose-100 dark:border-rose-900/30">
                            <div class="text-base font-bold text-rose-700 dark:text-rose-400">${{ formatPrice(order.price) }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ formatAmount(order.amount) }} {{ order.symbol }}</div>
                        </div>
                        <div v-if="sellOrders.length === 0" class="text-xs text-zinc-400 dark:text-zinc-500 text-center py-6 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700">
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
    initialSymbol: { type: String, default: '' }
});

const selectedSymbol = ref(props.initialSymbol);
const symbols = ['BTC', 'ETH'];
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

const formatPrice = (num) => {
    const n = parseFloat(num);
    if (n >= 1000) {
        return new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n);
    }
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
};

const formatAmount = (num) => {
    const n = parseFloat(num);
    if (n >= 1) {
        return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }).format(n);
    }
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 6 }).format(n);
};

const fetchOrders = async () => {
    loading.value = true;
    try {
        const url = selectedSymbol.value
            ? `/api/orders?symbol=${selectedSymbol.value}`
            : '/api/orders';
        const response = await fetch(url, {
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

const subscribeToChannel = (symbol) => {
    window.Echo.channel(`orderbook.${symbol}`)
        .listen('.OrderCreated', (e) => {
            if (!selectedSymbol.value || e.order.symbol === selectedSymbol.value) {
                orders.value.push(e.order);
            }
        })
        .listen('.OrderCancelled', (e) => {
            orders.value = orders.value.filter(o => o.id !== e.order.id);
        });
};

onMounted(() => {
    fetchOrders();

    // Listen for real-time updates via Pusher
    if (window.Echo) {
        // Subscribe to all symbol channels to receive all updates
        symbols.forEach(subscribeToChannel);
    }
});

</script>
