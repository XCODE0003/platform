<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * Number of digits in a generated account number.
     */
    public const ACCOUNT_NUMBER_LENGTH = 9;

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (empty($user->account_number)) {
                $user->account_number = self::generateUniqueAccountNumber();
            }
        });
    }

    /**
     * Generate a random, unique account number consisting of exactly
     * ACCOUNT_NUMBER_LENGTH digits (zero-padded, so leading zeros are kept).
     */
    public static function generateUniqueAccountNumber(): string
    {
        $max = (10 ** self::ACCOUNT_NUMBER_LENGTH) - 1;

        do {
            $number = str_pad((string) random_int(0, $max), self::ACCOUNT_NUMBER_LENGTH, '0', STR_PAD_LEFT);
        } while (self::where('account_number', $number)->exists());

        return $number;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    public function getFilamentName(): string
    {
        return $this->email;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'password',
        'google_2fa_enabled',
        'google_2fa_secret',
        'is_admin',
        'card_deposit_details',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'         => 'hashed',
            'is_admin'         => 'boolean',
        ];
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function depositWallets()
    {
        return $this->hasMany(DepositAddress::class);
    }



    public function wallets()
    {
        return $this->hasMany(UserWallet::class);
    }

    public function withdraws()
    {
        return $this->hasMany(Withdraw::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}

