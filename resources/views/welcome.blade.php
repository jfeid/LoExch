<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>LoExch - Limit Order Exchange</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    @auth
                        <a
                            href="{{ url('/dashboard') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal"
                        >
                            Dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                        >
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>
        <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
            <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row">
                <div class="text-[13px] leading-[20px] flex-1 p-6 pb-12 lg:p-20 bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-es-lg rounded-ee-lg lg:rounded-ss-lg lg:rounded-ee-none">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex aspect-square size-10 items-center justify-center rounded-md bg-emerald-600 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="size-6">
                                <path
                                    d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-9L21 12m0 0l-4.5 4.5M21 12H7.5"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    fill="none"
                                />
                            </svg>
                        </div>
                        <h1 class="text-xl font-semibold">LoExch</h1>
                    </div>
                    <p class="mb-2 text-[#706f6c] dark:text-[#A1A09A]">A simplified cryptocurrency limit order exchange.<br>Trade BTC and ETH against USD.</p>
                    <ul class="flex flex-col mb-4 lg:mb-6 space-y-2 mt-4">
                        <li class="flex items-center gap-3">
                            <span class="flex items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 w-6 h-6">
                                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </span>
                            <span>Place limit buy and sell orders</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 w-6 h-6">
                                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </span>
                            <span>Automatic order matching engine</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 w-6 h-6">
                                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </span>
                            <span>Real-time orderbook updates</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 w-6 h-6">
                                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </span>
                            <span>Maker-taker commission model</span>
                        </li>
                    </ul>
                    @guest
                    <div class="flex gap-3 mt-6">
                        <a href="{{ route('login') }}" class="inline-block px-5 py-1.5 bg-emerald-600 hover:bg-emerald-700 rounded-sm border border-emerald-600 hover:border-emerald-700 text-white text-sm leading-normal font-medium">
                            Log in
                        </a>
                        @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-block px-5 py-1.5 border border-zinc-300 dark:border-zinc-700 hover:border-zinc-400 dark:hover:border-zinc-600 rounded-sm text-sm leading-normal font-medium dark:text-[#EDEDEC]">
                            Register
                        </a>
                        @endif
                    </div>
                    @else
                    <div class="flex gap-3 mt-6">
                        <a href="{{ route('trading.overview') }}" class="inline-block px-5 py-1.5 bg-emerald-600 hover:bg-emerald-700 rounded-sm border border-emerald-600 hover:border-emerald-700 text-white text-sm leading-normal font-medium">
                            Start Trading
                        </a>
                    </div>
                    @endguest
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-950 relative lg:-ms-px -mb-px lg:mb-0 rounded-t-lg lg:rounded-t-none lg:rounded-e-lg! aspect-[335/376] lg:aspect-auto w-full lg:w-[438px] shrink-0 overflow-hidden flex items-center justify-center">
                    {{-- LoExch Visual --}}
                    <div class="flex flex-col items-center gap-6 p-10 transition-all translate-y-0 opacity-100 duration-750 starting:opacity-0 starting:translate-y-6">
                        <div class="flex aspect-square size-24 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="size-14">
                                <path
                                    d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-9L21 12m0 0l-4.5 4.5M21 12H7.5"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    fill="none"
                                />
                            </svg>
                        </div>
                        <div class="text-center">
                            <h2 class="text-3xl font-semibold text-emerald-900 dark:text-emerald-100">LoExch</h2>
                            <p class="text-emerald-700 dark:text-emerald-300 mt-1">Limit Order Exchange</p>
                        </div>
                        <div class="flex gap-6 mt-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-emerald-800 dark:text-emerald-200">BTC</div>
                                <div class="text-xs text-emerald-600 dark:text-emerald-400">Bitcoin</div>
                            </div>
                            <div class="text-emerald-400 dark:text-emerald-600 text-2xl">/</div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-emerald-800 dark:text-emerald-200">ETH</div>
                                <div class="text-xs text-emerald-600 dark:text-emerald-400">Ethereum</div>
                            </div>
                            <div class="text-emerald-400 dark:text-emerald-600 text-2xl">/</div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-emerald-800 dark:text-emerald-200">USD</div>
                                <div class="text-xs text-emerald-600 dark:text-emerald-400">US Dollar</div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute inset-0 rounded-t-lg lg:rounded-t-none lg:rounded-e-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"></div>
                </div>
            </main>
        </div>
    </body>
</html>
