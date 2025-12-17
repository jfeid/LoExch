<template>
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Place Order</h3>
        </div>

        <form @submit.prevent="submitOrder" class="p-5 space-y-5">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Symbol</label>
                <select v-model="form.symbol"
                    class="w-full px-3 py-2.5 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors">
                    <option value="BTC">BTC</option>
                    <option value="ETH">ETH</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Side</label>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" @click="form.side = 'buy'"
                        :class="[
                            form.side === 'buy'
                                ? 'bg-emerald-600 text-white ring-2 ring-emerald-600 ring-offset-2 dark:ring-offset-zinc-900'
                                : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700 border border-zinc-300 dark:border-zinc-600',
                            'py-2.5 px-4 rounded-lg font-semibold transition-all'
                        ]">
                        Buy
                    </button>
                    <button type="button" @click="form.side = 'sell'"
                        :class="[
                            form.side === 'sell'
                                ? 'bg-rose-600 text-white ring-2 ring-rose-600 ring-offset-2 dark:ring-offset-zinc-900'
                                : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700 border border-zinc-300 dark:border-zinc-600',
                            'py-2.5 px-4 rounded-lg font-semibold transition-all'
                        ]">
                        Sell
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Price (USD)</label>
                <input type="number" v-model="form.price" step="0.00000001" min="0"
                    class="w-full px-3 py-2.5 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors placeholder:text-zinc-400"
                    placeholder="0.00">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Amount</label>
                <input type="number" v-model="form.amount" step="0.00000001" min="0"
                    class="w-full px-3 py-2.5 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors placeholder:text-zinc-400"
                    placeholder="0.00">
            </div>

            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">Volume</span>
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ formatNumber(volume) }} USD</span>
                </div>
                <div class="flex justify-between text-sm mt-2">
                    <span class="text-zinc-500 dark:text-zinc-400">Fee ({{ feeLabel }})</span>
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ formatNumber(estimatedFee) }} USD</span>
                </div>
            </div>

            <div v-if="error" class="bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 p-4 rounded-lg text-sm font-medium">
                {{ error }}
            </div>

            <button type="submit" :disabled="loading"
                :class="[
                    form.side === 'buy'
                        ? 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500'
                        : 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500',
                    'w-full py-3 px-4 text-white font-semibold rounded-lg transition-colors disabled:opacity-50 focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-zinc-900'
                ]">
                {{ loading ? 'Processing...' : (form.side === 'buy' ? 'Place Buy Order' : 'Place Sell Order') }}
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    csrfToken: { type: String, required: true }
});

const emit = defineEmits(['orderCreated']);

const form = ref({
    symbol: 'BTC',
    side: 'buy',
    price: '',
    amount: ''
});

const loading = ref(false);
const error = ref('');

const volume = computed(() => {
    const price = parseFloat(form.value.price) || 0;
    const amount = parseFloat(form.value.amount) || 0;
    return price * amount;
});

const feeLabel = computed(() => form.value.side === 'buy' ? 'Taker 1.0%' : 'Maker 0.5%');

const estimatedFee = computed(() => {
    const feeRate = form.value.side === 'buy' ? 0.01 : 0.005;
    return volume.value * feeRate;
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 }).format(num);
};

const submitOrder = async () => {
    error.value = '';
    loading.value = true;

    try {
        const response = await fetch('/api/orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify(form.value)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Failed to create order');
        }

        emit('orderCreated', data.order);
        form.value.price = '';
        form.value.amount = '';
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
};
</script>
