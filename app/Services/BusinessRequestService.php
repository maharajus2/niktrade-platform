<?php

namespace App\Services;

use App\Models\BusinessRequest;
use App\Models\Contractor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessRequestService
{
    public function __construct(private readonly BusinessCartService $cart) {}

    public function submit(Contractor $contractor, ?string $comment = null): BusinessRequest
    {
        return DB::transaction(function () use ($contractor, $comment): BusinessRequest {
            $contractor = Contractor::whereKey($contractor->id)->lockForUpdate()->firstOrFail();
            $summary = $this->cart->summary($contractor, lock: true);
            if ($summary['items']->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Добавьте товары в заявку.']);
            }
            if (! $summary['can_submit']) {
                $message = $summary['missing'] > 0
                    ? 'Минимальная сумма заявки — '.number_format($summary['min_amount'], 2, ',', ' ').' ₽.'
                    : 'Проверьте наличие и цены товаров в заявке.';
                throw ValidationException::withMessages(['cart' => $message]);
            }
            $year = now()->year;
            // All contractors share the yearly sequence. The lock is held until commit.
            if (DB::getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(?, ?)', [73419, $year]);
            }
            $prefix = 'З-'.$year.'-';
            $last = BusinessRequest::where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');
            $sequence = $last === null ? 1 : (int) substr($last, -6) + 1;
            if ($sequence > 999999) {
                throw ValidationException::withMessages(['cart' => 'Лимит номеров заявок за год исчерпан. Обратитесь к менеджеру.']);
            }
            $request = BusinessRequest::create([
                'number' => $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
                'contractor_id' => $contractor->id, 'status' => 'new', 'comment' => $comment,
                'discount_percent' => $contractor->discount_percent, 'subtotal' => $summary['subtotal'],
                'total' => $summary['total'], 'items_count' => $summary['items_count'], 'manager_id' => $contractor->manager_id,
            ]);
            $request->items()->createMany($summary['items']->map(fn (array $item): array => [
                'product_id' => $item['product_id'], 'name' => $item['product']->name, 'article' => $item['product']->article,
                'quantity' => $item['quantity'], 'list_price' => $item['list_price'],
                'unit_price' => $item['unit_price'], 'line_total' => $item['line_total'],
            ])->all());
            $this->cart->clear($contractor);

            return $request->load('items', 'statusLogs');
        }, 3);
    }
}
