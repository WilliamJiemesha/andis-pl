<?php

namespace App\Http\Controllers;

use App\Models\InvoiceEntry;
use App\Models\IncomingCheckTask;
use App\Models\PriceReviewTask;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('dashboard', [
            'taskCards' => [
                ['label' => __('messages.items'), 'count' => \App\Models\MasterItem::query()->count(), 'icon' => 'fa-solid fa-cubes-stacked'],
                ['label' => __('messages.price_review'), 'count' => PriceReviewTask::query()->where('status', PriceReviewTask::STATUS_OPEN)->count(), 'icon' => 'fa-solid fa-tags'],
                ['label' => __('messages.incoming_check'), 'count' => IncomingCheckTask::query()->where('status', IncomingCheckTask::STATUS_PENDING)->count(), 'icon' => 'fa-solid fa-clipboard-check'],
                ['label' => __('messages.notifications'), 'count' => $request->user()->notifications()->whereNull('read_at')->count(), 'icon' => 'fa-regular fa-bell'],
            ],
            'recentIncomingItems' => InvoiceEntry::query()
                ->with('masterItem')
                ->latest('updated_at')
                ->take(5)
                ->get(),
        ]);
    }
}
