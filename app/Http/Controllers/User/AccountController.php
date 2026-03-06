<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\KycRequest;
use App\Http\Requests\User\WithdrawRequest;
use App\Models\KycUser;
use App\Models\Promocode;
use App\Models\Ticket;
use App\Models\Withdraw;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RobThree\Auth\Providers\Qr\QRServerProvider;
use RobThree\Auth\TwoFactorAuth;

class AccountController extends Controller
{
    /**
     * Display the user account page.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();
        $tfa = new TwoFactorAuth(new QRServerProvider(), config('app.name'));
        $label = $user->email;
        $qrText = $tfa->getQRText($label, $user->google_2fa_secret);

        $kycData = KycUser::where('user_id', $user->id)->first();

        $userTicket = Ticket::where('user_id', $user->id)
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')])
            ->latest()
            ->first();

        return Inertia::render('User/Account', [
            'qrText'     => $qrText,
            'kycData'    => $kycData,
            'userTicket' => $userTicket,
        ]);
    }

    /**
     * Request email change.
     */
    public function changeEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
        ]);

        // Здесь должна быть логика отправки кода подтверждения
        // Например, отправка email с кодом

        return back()->with('success', 'Confirmation code sent to your new email');
    }

    /**
     * Confirm email change.
     */
    public function confirmEmailChange(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'min:6'],
        ]);

        // Здесь должна быть логика проверки кода
        // Если код верный, обновляем email пользователя

        $user = $request->user();
        $user->email = $request->email;
        $user->email_verified_at = null; // Требуем повторной верификации
        $user->save();

        return back()->with('success', 'Email successfully changed');
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->password = bcrypt($request->password);
        $user->save();

        return back()->with('success', 'Password successfully changed');
    }



    /**
     * Activate promocode.
     */
    public function activatePromocode(Request $request)
    {
        $request->validate([
            'promocode' => ['required', 'string'],
        ]);

        $promocode = Promocode::where('code', $request->promocode)->first();

        if (!$promocode || !$promocode->is_active) {
            throw ValidationException::withMessages(['promocode' => 'Promocode not found or inactive.']);
        }

        if ($promocode->expiration_date->isPast()) {
            throw ValidationException::withMessages(['promocode' => 'Promocode has expired.']);
        }

        $user = $request->user();

        if ($promocode->usedBy($user->id)) {
            throw ValidationException::withMessages(['promocode' => 'You have already activated this promocode.']);
        }

        $wallet = $user->wallets()->where('currency_id', $promocode->currency_id)->first();
        if (!$wallet) {
            throw ValidationException::withMessages(['promocode' => 'No wallet found for this promocode currency.']);
        }

        DB::transaction(function () use ($user, $wallet, $promocode): void {
            // Re-check inside transaction to prevent race conditions
            $alreadyUsed = DB::table('promocode_users')
                ->where('promocode_id', $promocode->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyUsed) {
                throw ValidationException::withMessages(['promocode' => 'You have already activated this promocode.']);
            }

            $wallet->balance = bcadd((string) $wallet->balance, (string) $promocode->amount, 8);
            $wallet->save();

            $promocode->users()->attach($user->id, ['used_at' => now()]);
        });

        return back()->with('success', 'Promocode successfully activated');
    }

    /**
     * Process withdrawal request.
     */
    public function withdraw(WithdrawRequest $request): RedirectResponse
    {
        $user = $request->user();

        $kycApproved = KycUser::where('user_id', $user->id)->where('status', 'approved')->exists();
        if (! $kycApproved) {
            return back()->withErrors([
                'kyc' => 'KYC verification is required for withdrawals. Please complete verification in your account settings.',
            ]);
        }

        $bill = $request->bill();

        if (! $bill) {
            abort(404);
        }
        if($bill->demo){
            return back()->withErrors([
                'bill_id' => 'Withdrawals are not allowed from demo accounts.',
            ]);
        }

        $currency = $bill->currency;
        $amount = (float) $request->input('amount');

        $percentFee = (float) ($currency->send_percent ?? $currency->withdraw_fee ?? 0);
        $fixedFee = (float) ($currency->send_fixed ?? $currency->withdraw_fee_fixed ?? 0);

        $fee = round($amount * ($percentFee / 100), 8) + $fixedFee;
        $netAmount = round($amount - $fee, 8);

        if ($netAmount <= 0) {
            return back()->withErrors([
                'amount' => 'Amount is too small after fees.',
            ]);
        }

        DB::transaction(function () use ($bill, $user, $currency, $amount, $fee, $netAmount, $request): void {
            $lockedBill = $user->bills()
                ->whereKey($bill->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedBill) {
                throw ValidationException::withMessages([
                    'bill_id' => 'The selected balance is no longer available.',
                ]);
            }

            if ((float) $lockedBill->balance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient balance for this withdrawal.',
                ]);
            }

            $lockedBill->balance = number_format(((float) $lockedBill->balance) - $amount, 8, '.', '');
            $lockedBill->save();

            $user->withdraws()->create([
                'bill_id' => $lockedBill->id,
                'currency_id' => $currency?->id ?? $lockedBill->currency_id,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'address' => $request->input('address'),
                'status' => Withdraw::STATUS_PENDING,
                'meta' => [
                    'fee_percent' => $currency->send_percent ?? $currency->withdraw_fee ?? 0,
                    'fee_fixed'   => $currency->send_fixed ?? $currency->withdraw_fee_fixed ?? 0,
                    'network'     => $request->input('network'),
                ],
            ]);
        });

        return back()->with('success', 'Withdrawal request submitted successfully');
    }

    public function store(KycRequest $request)
    {
        $user = $request->user();
        $kycData = KycUser::where('user_id', $user->id)->first();

        if (!$kycData) {
            $kycData = new KycUser();
            $kycData->user_id = $user->id;
        }

        $kycData->fill($request->only([
            'sex', 'first_name', 'last_name', 'phone',
            'date_of_birth', 'country', 'city', 'address', 'zip_code',
        ]));

        $documents = $kycData->documents ?? [];
        $dir = 'kyc/' . $user->id;
        foreach (['document_front', 'document_back', 'document_selfie'] as $field) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store($dir, 'local');
                $key = str_replace('document_', '', $field);
                $documents[$key] = $path;
            }
        }
        $kycData->documents = $documents;
        $kycData->status = 'pending';
        $kycData->save();

        return redirect()->intended(route('account', absolute: false));
    }
    public function Toggle2FA(Request $request)
    {
        $user = $request->user();
        $tfa = new TwoFactorAuth(new QRServerProvider(), config('app.name'));
        $verify = $tfa->verifyCode($user->google_2fa_secret, $request->code);
        if(!$verify){
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Code is incorrect',
            ], 400);
        }

        $user->google_2fa_enabled = !$user->google_2fa_enabled;
        $user->save();
        return response()->json([
            'success' => true,
            'data' => null,
            'is_2fa' => $user->google_2fa_enabled,
            'message' => '2FA successfully updated',
        ], 200);
    }

    /**
     * Get KYC data for the user.
     */

}
