<template>
    <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow">
        <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-white">Wallet</h3>

        <div v-if="loading" class="text-zinc-400 text-sm">Loading...</div>

        <div v-else class="space-y-3">
            <!-- USD Balance -->
            <div class="flex justify-between items-center p-3 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                <span class="font-medium text-zinc-900 dark:text-white">USD</span>
                <span class="text-lg font-semibold text-zinc-900 dark:text-white">{{ formatNumber(profile.balance) }}</span>
            </div>

            <!-- Crypto Assets -->
            <div v-for="asset in profile.assets" :key="asset.symbol"
                class="flex justify-between items-center p-3 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                <div>
                    <span class="font-medium text-zinc-900 dark:text-white">{{ asset.symbol }}</span>
                    <span v-if="parseFloat(asset.locked_amount) > 0" class="text-xs text-zinc-500 dark:text-zinc-400 ml-2">
                        ({{ formatNumber(asset.locked_amount) }} locked)
                    </span>
                </div>
                <div class="text-right">
                    <div class="text-lg font-semibold text-zinc-900 dark:text-white">{{ formatNumber(asset.available) }}</div>
                    <div v-if="parseFloat(asset.locked_amount) > 0" class="text-xs text-zinc-500 dark:text-zinc-400">
                        Total: {{ formatNumber(asset.amount) }}
                    </div>
                </div>
            </div>

            <div v-if="profile.assets && profile.assets.length === 0" class="text-sm text-zinc-400 text-center py-2">
                No crypto assets
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const profile = ref({ balance: '0', assets: [] });
const loading = ref(true);

const formatNumber = (num) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 }).format(num);
};

const fetchProfile = async () => {
    try {
        const response = await fetch('/api/profile', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });
        const data = await response.json();
        profile.value = data.data || { balance: '0', assets: [] };
    } catch (e) {
        console.error('Failed to fetch profile:', e);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchProfile();

    // Listen for trade matches to update balances
    if (window.Echo) {
        const userId = document.querySelector('meta[name="user-id"]')?.content;
        if (userId) {
            window.Echo.private(`user.${userId}`)
                .listen('OrderMatched', () => {
                    fetchProfile();
                });
        }
    }
});

// Expose refresh method for parent components
defineExpose({ refresh: fetchProfile });
</script>
