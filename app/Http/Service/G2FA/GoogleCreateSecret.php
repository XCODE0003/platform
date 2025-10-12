<?php

namespace App\Http\Service\G2FA;

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

class GoogleCreateSecret
{
    public function createSecret()
    {
        return (new TwoFactorAuth(new QRServerProvider(), config('app.name')))->createSecret(160);
    }
}