<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Filament\Models\Contracts\HasName;


class User extends Authenticatable implements FilamentUser, HasName
{

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
    public function getFilamentName(): string
    {
        return $this->email;
    }
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [

        'email',
        'password',
        'google_2fa_enabled',
        'google_2fa_secret',
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
            'password' => 'hashed',
        ];
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
    public function DepositWallets()
    {
        return $this->hasMany(DepositAddress::class);
    }
    public function wallets()
    {
        return $this->hasMany(UserWallet::class);
    }
}
