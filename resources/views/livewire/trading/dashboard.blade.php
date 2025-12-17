<div>
    <div class="mb-6">
        <flux:heading size="xl">Trading Dashboard</flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
            Place orders and monitor the orderbook
        </flux:text>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left column: Order Form --}}
        <div class="lg:col-span-1">
            <div data-vue="OrderForm" data-props='@json(['csrfToken' => csrf_token()])'></div>
        </div>

        {{-- Middle column: Orderbook --}}
        <div class="lg:col-span-1">
            <div data-vue="Orderbook" data-props='@json(['initialSymbol' => 'BTC'])'></div>
        </div>

        {{-- Right column: Wallet --}}
        <div class="lg:col-span-1">
            <div data-vue="WalletOverview"></div>
        </div>
    </div>

    {{-- Order History --}}
    <div class="mt-6">
        <div data-vue="OrderHistory" data-props='@json(['csrfToken' => csrf_token()])'></div>
    </div>

    {{-- User ID meta tag for Echo --}}
    <meta name="user-id" content="{{ auth()->id() }}">
</div>
