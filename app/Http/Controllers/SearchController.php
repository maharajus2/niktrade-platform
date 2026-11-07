<?php

namespace App\Http\Controllers;

use App\Services\Search\ProductSearchService;
use App\Support\ProductName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SearchController extends Controller
{
    public function suggest(Request $request, ProductSearchService $search): JsonResponse
    {
        $query = trim(mb_substr($request->string('q')->toString(), 0, 80));

        if (mb_strlen($query) < 2) {
            return response()->json(['items' => [], 'total' => 0, 'fuzzy' => false]);
        }

        $result = $search->search($query, 6);

        return response()->json([
            'items' => array_map(fn (array $p): array => [
                'id' => $p['id'],
                'add_url' => route('cart.add', $p['slug']),
                'name' => ProductName::withoutBrand($p['name'], $p['brand'], $p['direction'] ?? null),
                'brand' => $p['brand'],
                'direction' => ProductName::directionLabel($p['direction'] ?? null),
                'price' => number_format($p['price'], 0, ',', ' ') . ' ₽',
                'image' => $p['image'] ? Storage::disk('public')->url($p['image']) : null,
                'url' => route('catalog.show', $p['slug']),
                'in_stock' => $p['in_stock'],
            ], $result['items']),
            'total' => $result['total'],
            'fuzzy' => $result['fuzzy'],
            'all_url' => route('catalog.index', ['search' => $query]),
        ]);
    }
}
