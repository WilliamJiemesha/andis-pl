<?php

namespace App\Http\Controllers;

use App\Models\MasterItem;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Foundation\Application;

class ItemSearchController extends Controller
{
    public function index(Request $request): View|Factory|Application
    {
        $query = MasterItem::query()->with([
            'incomingCheckTasks' => fn ($builder) => $builder->latest('updated_at'),
            'invoiceEntries' => fn ($builder) => $builder->latest('invoice_date')->latest('id'),
            'priceHistories' => fn ($builder) => $builder->latest('effective_at')->latest('id'),
            'costHistories' => fn ($builder) => $builder->latest('recorded_at')->latest('id'),
        ]);

        if ($search = trim((string) $request->string('q'))) {
            $searchUpper = mb_strtoupper($search);

            $query->where(function ($builder) use ($searchUpper, $search) {
                $builder
                    ->where('official_name', 'like', "%{$searchUpper}%")
                    ->orWhere('sku', 'like', "%{$searchUpper}%")
                    ->orWhere('barang', 'like', "%{$searchUpper}%")
                    ->orWhere('merk', 'like', "%{$searchUpper}%")
                    ->orWhere('tipe', 'like', "%{$searchUpper}%");
            });
        }

        $items = $query->orderBy('official_name')
            ->when($request->boolean('modal'), fn ($builder) => $builder->take(10))
            ->paginate($request->boolean('modal') ? 10 : 12)
            ->withQueryString();

        $data = [
            'items' => $items,
            'search' => $request->string('q')->value(),
            'canViewCostCode' => $request->user()->canViewCostCode(),
        ];

        if ($request->boolean('modal')) {
            return view('item-search._results', $data);
        }

        return view('item-search.index', $data);
    }
}
