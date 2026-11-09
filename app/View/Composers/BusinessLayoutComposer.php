<?php

namespace App\View\Composers;

use App\Services\BusinessCartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BusinessLayoutComposer
{
    public function __construct(private readonly BusinessCartService $cart) {}

    public function compose(View $view): void
    {
        $contractor = Auth::guard('business')->user();
        $summary = $contractor ? $this->cart->summary($contractor) : null;
        $view->with('bizCartCount', $summary['items_count'] ?? 0)->with('bizCartTotal', $summary['total'] ?? 0);
    }
}
