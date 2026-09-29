<?php

namespace App\Http\Controllers;

use App\Models\MasterItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterItemHistoryController extends Controller
{
    public function price(MasterItem $masterItem): View
    {
        $masterItem->load([
            'priceHistories' => fn ($builder) => $builder->with('changer')->latest('effective_at'),
        ]);

        return view('master-items.price-history', [
            'masterItem' => $masterItem,
        ]);
    }

    public function cost(MasterItem $masterItem, Request $request): View
    {
        abort_unless($request->user()->canViewDecodedCostAmount(), 403);

        $masterItem->load([
            'costHistories' => fn ($builder) => $builder->with(['recorder', 'invoiceEntry'])->latest('recorded_at'),
        ]);

        return view('master-items.cost-history', [
            'masterItem' => $masterItem,
        ]);
    }
}
