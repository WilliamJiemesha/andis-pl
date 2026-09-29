<?php

namespace App\Http\Controllers;

use App\Http\Requests\PriceReviewTask\ReviewPriceTaskRequest;
use App\Models\PriceHistory;
use App\Models\PriceReviewTask;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceReviewTaskController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Request $request): View
    {
        $query = PriceReviewTask::query()->with(['masterItem', 'invoiceEntry', 'costHistory', 'reviewer']);
        $status = $request->string('status')->value() ?: PriceReviewTask::STATUS_OPEN;

        if ($status) {
            $query->where('status', $status);
        }

        return view('price-review-tasks.index', [
            'tasks' => $query->latest()->paginate(12)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(PriceReviewTask $priceReviewTask): View
    {
        $priceReviewTask->load([
            'masterItem.costHistories' => fn ($builder) => $builder->latest('recorded_at')->latest('id'),
            'invoiceEntry',
            'costHistory',
            'reviewer',
        ]);

        return view('price-review-tasks.show', [
            'task' => $priceReviewTask,
        ]);
    }

    public function review(ReviewPriceTaskRequest $request, PriceReviewTask $priceReviewTask): RedirectResponse
    {
        $validated = $request->validated();
        $masterItem = $priceReviewTask->masterItem;
        $previousPrice = $masterItem->selling_price;
        $newPrice = (float) $validated['new_selling_price'];
        $priceChanged = $previousPrice === null
            ? $validated['new_selling_price'] !== null
            : (float) $previousPrice !== $newPrice;

        if ($priceChanged) {
            $masterItem->update([
                'selling_price' => $validated['new_selling_price'],
                'updated_by' => $request->user()->id,
            ]);

            PriceHistory::query()->create([
                'master_item_id' => $masterItem->id,
                'previous_price' => $previousPrice,
                'new_price' => $validated['new_selling_price'],
                'changed_by' => $request->user()->id,
                'note' => $validated['review_note'] ?? null,
                'effective_at' => now(),
            ]);

            $notificationType = $previousPrice !== null && $newPrice < (float) $previousPrice
                ? 'price_updated_down'
                : 'price_updated_up';

            $this->notificationService->notifyRoles(
                [\App\Models\Role::SALES_USER, \App\Models\Role::ADMIN],
                $notificationType,
                'Perubahan Harga',
                $notificationType === 'price_updated_down'
                    ? 'Harga jual turun untuk '.$masterItem->official_name.'.'
                    : 'Harga jual naik untuk '.$masterItem->official_name.'.',
                route('master-items.price-history', $masterItem),
                \App\Models\MasterItem::class,
                $masterItem->id,
            );
        }

        $priceReviewTask->update([
            'status' => PriceReviewTask::STATUS_REVIEWED,
            'review_note' => $validated['review_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('price-review-tasks.show', $priceReviewTask)
            ->with('status', $priceChanged
                ? __('messages.price_review_updated_price')
                : __('messages.price_review_kept_price'));
    }
}
