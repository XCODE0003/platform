<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display the user account page.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

        // Получаем KYC данные пользователя (если есть модель KYC)
        $kycData = $this->getKycData($user);

        return Inertia::render('User/Account', [
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid ?? $user->id,
                'email' => $user->email,
                'is_2fa' => $user->two_factor_secret !== null,
                'timezone' => $user->timezone ?? 'UTC',
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
            ],
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
     * Enable 2FA.
     */
    public function enable2FA(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'min:6'],
        ]);

        // Здесь должна быть логика проверки кода 2FA
        // Например, проверка кода из Google Authenticator

        $user = $request->user();
        $user->two_factor_secret = 'sample_secret'; // Замените на реальный секрет
        $user->save();

        return back()->with('success', '2FA successfully enabled');
    }

    /**
     * Disable 2FA.
     */
    public function disable2FA(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'min:6'],
        ]);

        // Здесь должна быть логика проверки кода 2FA

        $user = $request->user();
        $user->two_factor_secret = null;
        $user->save();

        return back()->with('success', '2FA successfully disabled');
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

    /**
     * Get KYC data for the user.
     */
    private function getKycData($user): array
    {
        // Если у вас есть модель KYC, замените на реальную логику
        // Например: $kyc = $user->kyc;

        // Пока возвращаем пустые данные
        return [
            'sex' => '',
            'first_name' => '',
            'last_name' => '',
            'phone' => '',
            'dateOfBrith' => '',
            'country' => '',
            'city' => '',
            'address' => '',
            'zip_code' => '',
            'kyc_documents' => [],
            'kyc_status' => 0, // 0 - не подан, 1 - на рассмотрении, 2 - отклонен, 3 - одобрен
            'error_message' => '',
        ];
    }
}
