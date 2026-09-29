<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemResolutionTicket\CreateMasterItemFromTicketRequest;
use App\Http\Requests\ItemResolutionTicket\LinkTicketMasterItemRequest;
use App\Models\InvoiceEntry;
use App\Models\ItemResolutionTicket;
use App\Models\MasterItem;
use App\Services\IncomingCheckService;
use App\Services\NotificationService;
use App\Services\PriceReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemResolutionTicketController extends Controller
{
    public function __construct(
        private readonly PriceReviewService $priceReviewService,
        private readonly IncomingCheckService $incomingCheckService,
        private readonly NotificationService $notificationService,
    )
    {
    }

    public function index(Request $request): View
    {
        $query = ItemResolutionTicket::query()->with(['invoiceEntry.masterItem', 'creator', 'resolver', 'resolvedMasterItem']);
        $status = $request->string('status')->value() ?: ItemResolutionTicket::STATUS_OPEN;

        if ($search = trim((string) $request->string('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('raw_item_name', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return view('item-resolution-tickets.index', [
            'tickets' => $query->latest()->paginate(12)->withQueryString(),
            'search' => $request->string('q')->value(),
            'status' => $status,
        ]);
    }

    public function show(ItemResolutionTicket $itemResolutionTicket): View
    {
        $itemResolutionTicket->load(['invoiceEntry', 'creator', 'resolver', 'resolvedMasterItem']);

        $suggestedItems = MasterItem::query()
            ->where(function ($builder) use ($itemResolutionTicket) {
                $search = mb_strtoupper($itemResolutionTicket->raw_item_name);

                $builder
                    ->where('official_name', 'like', "%{$search}%")
                    ->orWhere('barang', 'like', "%{$search}%")
                    ->orWhere('merk', 'like', "%{$search}%")
                    ->orWhere('tipe', 'like', "%{$search}%");
            })
            ->orderBy('official_name')
            ->take(8)
            ->get();

        return view('item-resolution-tickets.show', [
            'ticket' => $itemResolutionTicket,
            'masterItems' => MasterItem::query()->orderBy('official_name')->get(),
            'suggestedItems' => $suggestedItems,
        ]);
    }

    public function link(LinkTicketMasterItemRequest $request, ItemResolutionTicket $itemResolutionTicket): RedirectResponse
    {
        $masterItem = MasterItem::query()->findOrFail($request->validated('master_item_id'));
        $invoiceEntry = $itemResolutionTicket->invoiceEntry;

        $invoiceEntry->update([
            'master_item_id' => $masterItem->id,
            'status' => InvoiceEntry::STATUS_MATCHED,
        ]);
        $invoiceEntry->load('masterItem');

        $itemResolutionTicket->update([
            'status' => ItemResolutionTicket::STATUS_RESOLVED,
            'resolved_by' => $request->user()->id,
            'resolved_master_item_id' => $masterItem->id,
            'resolution_type' => ItemResolutionTicket::RESOLUTION_LINK_EXISTING,
            'resolution_note' => $request->validated('resolution_note'),
            'resolved_at' => now(),
        ]);

        $this->priceReviewService->recordInvoiceCostChange($invoiceEntry);
        $this->incomingCheckService->ensureTaskForInvoiceEntry($invoiceEntry);
        $this->notificationService->notifyRoles(
            [\App\Models\Role::SALES_USER, \App\Models\Role::ADMIN],
            'ticket_resolved',
            __('messages.item_resolution_tickets'),
            __('messages.notification_ticket_resolved', ['name' => $masterItem->official_name]),
            route('master-items.show', $masterItem),
            MasterItem::class,
            $masterItem->id,
        );

        return redirect()
            ->route('item-resolution-tickets.show', $itemResolutionTicket)
            ->with('status', __('messages.ticket_linked_success'));
    }

    public function createMasterItem(CreateMasterItemFromTicketRequest $request, ItemResolutionTicket $itemResolutionTicket): RedirectResponse
    {
        $validated = $request->validated();

        $masterItem = MasterItem::query()->create([
            'barang' => $validated['barang'],
            'merk' => $validated['merk'],
            'tipe' => $validated['tipe'],
            'selling_price' => $validated['selling_price'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'],
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $itemResolutionTicket->invoiceEntry->update([
            'master_item_id' => $masterItem->id,
            'status' => InvoiceEntry::STATUS_MATCHED,
        ]);
        $itemResolutionTicket->invoiceEntry->load('masterItem');

        $itemResolutionTicket->update([
            'status' => ItemResolutionTicket::STATUS_RESOLVED,
            'resolved_by' => $request->user()->id,
            'resolved_master_item_id' => $masterItem->id,
            'resolution_type' => ItemResolutionTicket::RESOLUTION_CREATE_NEW,
            'resolution_note' => $validated['resolution_note'] ?? null,
            'resolved_at' => now(),
        ]);

        $this->priceReviewService->recordInvoiceCostChange($itemResolutionTicket->invoiceEntry);
        $this->incomingCheckService->ensureTaskForInvoiceEntry($itemResolutionTicket->invoiceEntry);
        $this->notificationService->notifyRoles(
            [\App\Models\Role::SALES_USER, \App\Models\Role::ADMIN],
            'ticket_resolved_created_master',
            __('messages.item_resolution_tickets'),
            __('messages.notification_ticket_created_master', ['name' => $masterItem->official_name]),
            route('master-items.show', $masterItem),
            MasterItem::class,
            $masterItem->id,
        );

        return redirect()
            ->route('item-resolution-tickets.show', $itemResolutionTicket)
            ->with('status', __('messages.ticket_created_master_success'));
    }
}
