<?php

declare(strict_types=1);

namespace App\Models;

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
}

