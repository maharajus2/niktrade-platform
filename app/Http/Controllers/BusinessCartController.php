<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\BusinessCartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BusinessCartController extends Controller
{
    public function __construct(private readonly BusinessCartService $cart) {}

    public function index(Request $request): View
    {
        return view('business.cart.index', ['summary' => $this->cart->summary($request->user('business'))]);
    }

    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer', 'exists:products,id'], 'quantity' => ['sometimes', 'integer', 'min:1', 'max:9999']]);
        $product = Product::findOrFail($data['product_id']);
        $this->cart->add($request->user('business'), $product, $data['quantity'] ?? 1);

        return $this->response($request, $product);
    }

    public function update(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:9999']]);
        $this->cart->setQuantity($request->user('business'), $product, $data['quantity']);

        return $this->response($request, $product);
    }

    public function remove(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $this->cart->remove($request->user('business'), $product);

        return $this->response($request, $product);
    }

    private function response(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return back()->with('success', 'Заявка обновлена.');
        }
        $summary = $this->cart->summary($request->user('business'));
        $item = $summary['items']->firstWhere('product_id', $product->id);

        return response()->json([
            'summary' => array_intersect_key($summary, array_flip(['items_count', 'total', 'missing', 'can_submit', 'min_amount'])),
            'item' => $item === null ? null : array_intersect_key($item, array_flip(['product_id', 'quantity', 'line_total'])),
        ]);
    }
}
