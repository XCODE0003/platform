<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\KycRequest;
use Inertia\Response;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;
use App\Models\KycUser;
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

        return Inertia::render('User/Account', [
            'qrText' => $qrText,
            'kycData' => $kycData,
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

        // Здесь должна быть логика проверки и активации промокода
        // Например, поиск промокода в базе данных и начисление бонуса

        return back()->with('success', 'Promocode successfully activated');
    }

    /**
     * Process withdrawal request.
     */
    public function withdraw(Request $request)
    {
        $request->validate([
            'currency_id' => ['required', 'exists:user_wallets,id'],
            'address' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.00000001'],
        ]);

        $user = $request->user();
        $wallet = $user->wallets()->findOrFail($request->currency_id);

        // Проверяем достаточность средств
        if ($wallet->balance < $request->amount) {
            return back()->withErrors(['amount' => 'Insufficient balance']);
        }

        // Здесь должна быть логика создания заявки на вывод
        // Например, создание записи в таблице withdrawals

        // Временно просто уменьшаем баланс
        $wallet->balance -= $request->amount;
        $wallet->save();

        return back()->with('success', 'Withdrawal request submitted successfully');
    }

    public function store(KycRequest $request)
    {
        $user = $request->user();
        $kycData = KycUser::where('user_id', $user->id)->first();

        if (!$kycData) {
            $kycData = new KycUser();
            $kycData->user_id = $user->id;
            $kycData->status = 'pending';
        }

        $kycData->fill($request->all());
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
