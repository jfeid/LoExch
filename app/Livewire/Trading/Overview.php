<?php

namespace App\Livewire\Trading;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Overview extends Component
{
    public function render(): View
    {
        return view('livewire.trading.overview')
            ->layout('components.layouts.app', ['title' => 'Overview']);
    }
}
