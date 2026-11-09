<?php

namespace App\Http\Controllers;

use App\Models\BusinessRequest;
use App\Services\BusinessRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BusinessRequestController extends Controller
{
    public function store(Request $request, BusinessRequestService $service): RedirectResponse
    {
        $data = $request->validate(['comment' => ['nullable', 'string', 'max:1000']]);
        $businessRequest = $service->submit($request->user('business'), $data['comment'] ?? null);

        return redirect()->route('business.requests.show', $businessRequest->number)->with('success', 'Заявка отправлена.');
    }

    public function index(Request $request): View
    {
        return view('business.requests.index', ['requests' => $request->user('business')->requests()->latest('id')->paginate(15)]);
    }

    public function show(Request $request, string $number): View
    {
        return view('business.requests.show', ['businessRequest' => $this->owned($request, $number)->load('items', 'manager', 'statusLogs.user')]);
    }

    public function invoice(Request $request, string $number): StreamedResponse
    {
        $businessRequest = $this->owned($request, $number);
        abort_unless(filled($businessRequest->invoice_path) && Storage::disk('local')->exists($businessRequest->invoice_path), 404);

        return Storage::disk('local')->download($businessRequest->invoice_path, 'invoice-'.$businessRequest->id.'.pdf', ['Content-Type' => 'application/pdf']);
    }

    private function owned(Request $request, string $number): BusinessRequest
    {
        return $request->user('business')->requests()->where('number', $number)->firstOrFail();
    }
}
