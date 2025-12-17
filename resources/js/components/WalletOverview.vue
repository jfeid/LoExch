<template>
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Wallet</h3>
        </div>

        <div class="p-5">
            <div v-if="loading" class="text-zinc-400 dark:text-zinc-500 text-sm py-8 text-center">Loading...</div>

            <div v-else class="space-y-3">
                <!-- USD Balance -->
                <div class="flex justify-between items-center p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-100 dark:border-blue-900/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-sm">$</div>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">USD</span>
                    </div>
                    <span class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ formatNumber(profile.balance) }}</span>
                </div>

                <!-- Crypto Assets -->
                <div v-for="asset in profile.assets" :key="asset.symbol"
                    class="flex justify-between items-center p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-3">
                        <div :class="[
                            asset.symbol === 'BTC' ? 'bg-amber-500' : 'bg-indigo-500',
                            'w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-xs'
                        ]">{{ asset.symbol }}</div>
                        <div>
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ asset.symbol }}</span>
                            <span v-if="parseFloat(asset.locked_amount) > 0" class="text-xs text-zinc-500 dark:text-zinc-400 ml-2">
                                ({{ formatNumber(asset.locked_amount) }} locked)
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ formatNumber(asset.available) }}</div>
                        <div v-if="parseFloat(asset.locked_amount) > 0" class="text-xs text-zinc-500 dark:text-zinc-400">
                            Total: {{ formatNumber(asset.amount) }}
                        </div>
                    </div>
                </div>

                <div v-if="profile.assets && profile.assets.length === 0"
                    class="text-sm text-zinc-400 dark:text-zinc-500 text-center py-8 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700">
                    No crypto assets
                </div>
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

    // Listen for balance changes via Pusher
    if (window.Echo) {
        const userId = document.querySelector('meta[name="user-id"]')?.content;
        if (userId) {
            window.Echo.private(`user.${userId}`)
                .listen('.OrderCreated', () => {
                    fetchProfile();
                })
                .listen('.OrderCancelled', () => {
                    fetchProfile();
                })
                .listen('.OrderMatched', () => {
                    fetchProfile();
                });
        }
    }
});

// Expose refresh method for parent components
defineExpose({ refresh: fetchProfile });
</script>
