<div>
    <div class="mb-6">
        <flux:heading size="xl">Place Order</flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
            Create a new limit order
        </flux:text>
    </div>

    <div class="max-w-md">
        <div data-vue="OrderForm" data-props='@json(['csrfToken' => csrf_token()])'></div>
    </div>

    {{-- User ID meta tag for Echo --}}
    <meta name="user-id" content="{{ auth()->id() }}">
</div>
