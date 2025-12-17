<?php

namespace App\Livewire\Trading;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): View
    {
        return view('livewire.trading.dashboard')
            ->layout('components.layouts.app', ['title' => 'Trading']);
    }
}
