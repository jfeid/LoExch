<template>
    <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow">
        <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-white">Place Order</h3>

        <form @submit.prevent="submitOrder" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Symbol</label>
                <select v-model="form.symbol" class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="BTC">BTC</option>
                    <option value="ETH">ETH</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Side</label>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="form.side = 'buy'"
                        :class="[form.side === 'buy' ? 'bg-green-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300', 'py-2 px-4 rounded-md font-medium transition-colors']">
                        Buy
                    </button>
                    <button type="button" @click="form.side = 'sell'"
                        :class="[form.side === 'sell' ? 'bg-red-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300', 'py-2 px-4 rounded-md font-medium transition-colors']">
                        Sell
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Price (USD)</label>
                <input type="number" v-model="form.price" step="0.00000001" min="0"
                    class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="0.00">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Amount</label>
                <input type="number" v-model="form.amount" step="0.00000001" min="0"
                    class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="0.00">
            </div>

            <div class="bg-zinc-50 dark:bg-zinc-700 rounded-md p-3">
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-600 dark:text-zinc-400">Volume</span>
                    <span class="font-medium text-zinc-900 dark:text-white">{{ formatNumber(volume) }} USD</span>
                </div>
                <div class="flex justify-between text-sm mt-1">
                    <span class="text-zinc-600 dark:text-zinc-400">Fee ({{ form.side === 'buy' ? '1.0%' : '0.5%' }})</span>
                    <span class="font-medium text-zinc-900 dark:text-white">{{ formatNumber(estimatedFee) }} USD</span>
                </div>
            </div>

            <div v-if="error" class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-3 rounded-md text-sm">
                {{ error }}
            </div>

            <button type="submit" :disabled="loading"
                :class="[form.side === 'buy' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700', 'w-full py-2 px-4 text-white font-medium rounded-md transition-colors disabled:opacity-50']">
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
