<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncomingCheckTask\UpdateIncomingCheckTaskRequest;
use App\Models\IncomingCheckTask;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncomingCheckTaskController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Request $request): View
    {
        $query = IncomingCheckTask::query()->with(['invoiceEntry', 'masterItem', 'checker']);
        $status = $request->string('status')->value() ?: IncomingCheckTask::STATUS_PENDING;
        $date = $request->date('date');

        if ($status === 'checked') {
            $query->whereIn('status', [IncomingCheckTask::STATUS_OK, IncomingCheckTask::STATUS_MISMATCH]);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($status === IncomingCheckTask::STATUS_PENDING) {
            $date = null;
        }

        if ($date) {
            $query->whereHas('invoiceEntry', fn ($invoiceEntryQuery) => $invoiceEntryQuery->whereDate('invoice_date', $date));
        }

        return view('incoming-check-tasks.index', [
            'tasks' => $query
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'status' => $status,
            'date' => $date?->toDateString(),
        ]);
    }

    public function show(IncomingCheckTask $incomingCheckTask): View
    {
        $incomingCheckTask->load(['invoiceEntry', 'masterItem', 'checker']);

        return view('incoming-check-tasks.show', [
            'task' => $incomingCheckTask,
        ]);
    }

    public function update(UpdateIncomingCheckTaskRequest $request, IncomingCheckTask $incomingCheckTask): RedirectResponse
    {
        $validated = $request->validated();
        $resolvedStatus = $request->resolvedStatus();

        $incomingCheckTask->update([
            'checked_quantity' => $validated['checked_quantity'],
            'status' => $resolvedStatus,
            'notes' => $validated['notes'] ?? null,
            'checked_by' => $request->user()->id,
            'checked_at' => now(),
        ]);

        $this->notificationService->notifyRoles(
            [\App\Models\Role::ADMIN, \App\Models\Role::INVOICE_HANDLER],
            $resolvedStatus === IncomingCheckTask::STATUS_MISMATCH ? 'incoming_mismatch' : 'incoming_checked',
            'Cek Barang',
            $resolvedStatus === IncomingCheckTask::STATUS_MISMATCH
                ? 'Cek barang menemukan selisih untuk '.($incomingCheckTask->masterItem?->official_name ?? '-').'.'
                : 'Cek barang selesai untuk '.($incomingCheckTask->masterItem?->official_name ?? '-').'.',
            route('incoming-check-tasks.show', $incomingCheckTask),
            IncomingCheckTask::class,
            $incomingCheckTask->id,
        );

        $redirectRoute = $request->input('redirect_to') === 'index'
            ? redirect()->route('incoming-check-tasks.index', ['status' => IncomingCheckTask::STATUS_PENDING])
            : redirect()->route('incoming-check-tasks.show', $incomingCheckTask);

        return $redirectRoute
            ->with('status', $resolvedStatus === IncomingCheckTask::STATUS_MISMATCH
                ? __('messages.incoming_check_marked_mismatch')
                : __('messages.incoming_check_marked_ok'));
    }
}
