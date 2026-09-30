<?php

namespace App\Http\Controllers;

use App\Http\Requests\PriceReviewTask\ReviewPriceTaskRequest;
use App\Models\PriceHistory;
use App\Models\PriceReviewTask;
use App\Models\Role;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $priceChanged = $this->completeReview(
            $priceReviewTask,
            (float) $validated['new_selling_price'],
            $request->user()->id,
            $validated['review_note'] ?? null,
        );

        return redirect()
            ->route('price-review-tasks.show', $priceReviewTask)
            ->with('status', $priceChanged
                ? __('messages.price_review_updated_price')
                : __('messages.price_review_kept_price'));
    }

    public function batchReview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reviews' => ['nullable', 'array'],
            'reviews.*.new_selling_price' => ['nullable', 'string'],
        ]);

        $reviewRows = collect($validated['reviews'] ?? [])
            ->map(fn (array $row) => preg_replace('/\D+/', '', (string) ($row['new_selling_price'] ?? '')))
            ->filter(fn (?string $price) => filled($price))
            ->map(fn (string $price) => (float) $price);

        if ($reviewRows->isEmpty()) {
            return redirect()
                ->route('price-review-tasks.index', ['status' => PriceReviewTask::STATUS_OPEN])
                ->with('status', __('messages.price_review_batch_empty'));
        }

        $tasks = PriceReviewTask::query()
            ->with('masterItem')
            ->whereIn('id', $reviewRows->keys())
            ->where('status', PriceReviewTask::STATUS_OPEN)
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($reviewRows, $tasks, $request): void {
            foreach ($reviewRows as $taskId => $newPrice) {
                $task = $tasks->get((int) $taskId);

                if (! $task) {
                    continue;
                }

                $this->completeReview($task, $newPrice, $request->user()->id, null);
            }
        });

        return redirect()
            ->route('price-review-tasks.index', ['status' => PriceReviewTask::STATUS_OPEN])
            ->with('status', __('messages.price_review_batch_completed', ['count' => $tasks->count()]));
    }

    private function completeReview(PriceReviewTask $priceReviewTask, float $newPrice, int $userId, ?string $reviewNote): bool
    {
        $masterItem = $priceReviewTask->masterItem;
        $previousPrice = $masterItem->selling_price;
        $priceChanged = $previousPrice === null
            ? true
            : (float) $previousPrice !== $newPrice;

        if ($priceChanged) {
            $masterItem->update([
                'selling_price' => $newPrice,
                'updated_by' => $userId,
            ]);

            PriceHistory::query()->create([
                'master_item_id' => $masterItem->id,
                'previous_price' => $previousPrice,
                'new_price' => $newPrice,
                'changed_by' => $userId,
                'note' => $reviewNote,
                'effective_at' => now(),
            ]);

            $notificationType = $previousPrice !== null && $newPrice < (float) $previousPrice
                ? 'price_updated_down'
                : 'price_updated_up';

            $this->notificationService->notifyRoles(
                [Role::SALES_USER, Role::ADMIN],
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
            'review_note' => $reviewNote,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
        ]);

        return $priceChanged;
    }
}
