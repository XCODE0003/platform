<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateWalletAddress;
use App\Models\Currency;
use App\Http\Service\User\CreateWallets;
use App\Models\User;
use App\Http\Service\G2FA\GoogleCreateSecret;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'phone'      => ['required', 'string', 'max:30'],
            'email'      => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password'   => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $secret = (new GoogleCreateSecret())->createSecret();
        $user = User::create([
            'first_name'        => $request->first_name,
            'last_name'         => $request->last_name,
            'phone'             => $request->phone,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'google_2fa_secret' => $secret,
        ]);
        (new CreateWallets())->createWallets($user);
        event(new Registered($user));

        $currencies = Currency::all();
        foreach ($currencies as $currency) {
            GenerateWalletAddress::dispatch($user, $currency);
        }

        Auth::login($user);

        $request->session()->regenerate();

        return to_route('assets');
    }
}
