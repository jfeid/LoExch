<div>
    <div class="mb-6">
        <flux:heading size="xl">Overview</flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
            View your wallet, orders, and the orderbook
        </flux:text>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Left column: Wallet --}}
        <div>
            <div data-vue="WalletOverview"></div>
        </div>

        {{-- Right column: Orderbook --}}
        <div>
            <div data-vue="Orderbook"></div>
        </div>
    </div>

    {{-- Order History --}}
    <div class="mt-6">
        <div data-vue="OrderHistory" data-props='@json(['csrfToken' => csrf_token()])'></div>
    </div>

    {{-- User ID meta tag for Echo --}}
    <meta name="user-id" content="{{ auth()->id() }}">
</div>
