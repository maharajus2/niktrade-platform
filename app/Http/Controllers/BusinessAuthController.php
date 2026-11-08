<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Rules\UniqueContractorIdentity;
use App\Rules\ValidInn;
use App\Rules\ValidKpp;
use App\Support\ContractorIdentity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class BusinessAuthController extends Controller
{
    public function home(): View|RedirectResponse
    {
        if (Auth::guard('business')->check()) {
            return redirect()->route('business.account');
        }

        return view('business.home');
    }

    public function register(Request $request): RedirectResponse
    {
        $inn = ContractorIdentity::inn($request->input('inn'));
        $company = is_string($inn) && strlen($inn) === 10;
        $request->merge([
            'inn' => $inn,
            'kpp' => is_string($inn) && strlen($inn) === 12 ? null : ContractorIdentity::kpp($request->input('kpp')),
            'email' => is_string($request->input('email')) ? strtolower(trim($request->input('email'))) : $request->input('email'),
        ]);

        $data = $request->validateWithBag('register', [
            'company_name' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'string', new ValidInn, new UniqueContractorIdentity($request->input('kpp'))],
            'kpp' => [$company ? 'required' : 'nullable', 'string', new ValidKpp],
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:contractors,email'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ]);

        $contractor = Contractor::create([...$data, 'is_active' => true, 'discount_percent' => 0]);
        Auth::guard('business')->login($contractor);
        $request->session()->regenerate();

        return redirect()->route('business.account');
    }

    public function login(Request $request): RedirectResponse
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }

        $credentials = $request->validateWithBag('login', [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $contractor = Contractor::where('email', $credentials['email'])->first();
        if ($contractor && ! $contractor->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Учётная запись отключена, обратитесь к менеджеру',
            ])->errorBag('login');
        }

        if (! Auth::guard('business')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Неверный email или пароль'])->errorBag('login');
        }

        Auth::guard('business')->user()->update(['last_login_at' => now()]);
        $request->session()->regenerate();

        return redirect()->intended(route('business.account'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('business')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('business.home');
    }

    public function account(): View
    {
        return view('business.account', ['contractor' => Auth::guard('business')->user()]);
    }
}
