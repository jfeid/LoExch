<?php

namespace App\Livewire\Trading;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class OrderForm extends Component
{
    public function render(): View
    {
        return view('livewire.trading.order-form')
            ->layout('components.layouts.app', ['title' => 'Place Order']);
    }
}
