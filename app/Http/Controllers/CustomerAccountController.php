<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerAccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $customer = $request->user('customer');

        return view('customer-account.dashboard', [
            'customer' => $customer,
            'ordersCount' => $customer->orders()->count(),
            'addressesCount' => $customer->addresses()->count(),
            'totalSpent' => $customer->orders()
                ->where('status', Order::STATUS_DELIVERED)
                ->sum('total'),
            'totalOrderedWeightGrams' => $customer->orders()
                ->sum('total_weight_grams'),
        ]);
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $customer = $request->user('customer');
        $oldAvatarPath = $customer->avatar_path;
        $avatarPath = $data['avatar']->store('customer-avatars', 'public');

        $customer->update([
            'avatar_path' => $avatarPath,
        ]);

        if ($oldAvatarPath) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        return back()->with('success', 'Аватар обновлён.');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');

        if ($customer->avatar_path) {
            Storage::disk('public')->delete($customer->avatar_path);
        }

        $customer->update([
            'avatar_path' => null,
        ]);

        return back()->with('success', 'Аватар удалён.');
    }
}
