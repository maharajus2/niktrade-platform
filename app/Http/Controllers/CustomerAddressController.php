<?php

namespace App\Http\Controllers;

use App\Models\CustomerAddress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer-account.addresses.index', [
            'customer' => $request->user('customer'),
            'addresses' => $request->user('customer')
                ->addresses()
                ->orderByDesc('is_default')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');
        $data = $this->validateAddress($request);
        $data['is_default'] = $request->boolean('is_default');

        if (! $customer->addresses()->exists()) {
            $data['is_default'] = true;
        }

        $customer->addresses()->create($data);

        return back()->with('success', 'Адрес сохранён.');
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        abort_unless($address->customer_id === $request->user('customer')?->id, 403);

        $data = $this->validateAddress($request);
        $data['is_default'] = $request->boolean('is_default');

        $address->update($data);

        return back()->with('success', 'Адрес обновлён.');
    }

    public function destroy(Request $request, CustomerAddress $address): RedirectResponse
    {
        abort_unless($address->customer_id === $request->user('customer')?->id, 403);

        $address->delete();

        return back()->with('success', 'Адрес удалён.');
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'house' => ['required', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'apartment' => ['nullable', 'string', 'max:255'],
            'entrance' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
        ]);
    }
}
