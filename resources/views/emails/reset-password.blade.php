<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset your password</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a1520; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #e0e6ed; }
        .wrapper { max-width: 560px; margin: 40px auto; padding: 0 16px 60px; }
        .logo { text-align: center; padding: 32px 0 24px; font-size: 22px; font-weight: 700; color: #79F995; letter-spacing: 0.5px; }
        .card { background: #111d2b; border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #0f2a1e 0%, #0a1f2b 100%); padding: 36px 40px 28px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .card-header h1 { font-size: 22px; font-weight: 700; color: #fff; margin-bottom: 8px; }
        .card-header p { font-size: 14px; color: rgba(255,255,255,0.55); line-height: 1.5; }
        .card-body { padding: 32px 40px; }
        .card-body p { font-size: 15px; color: rgba(255,255,255,0.75); line-height: 1.7; margin-bottom: 16px; }
        .btn-wrap { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; background: #79F995; color: #071510; font-size: 15px; font-weight: 700; padding: 14px 40px; border-radius: 10px; text-decoration: none; letter-spacing: 0.3px; }
        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.06); margin: 24px 0; }
        .link-fallback { font-size: 12px; color: rgba(255,255,255,0.35); line-height: 1.6; word-break: break-all; }
        .link-fallback a { color: rgba(121,249,149,0.7); }
        .expire-note { font-size: 13px; color: rgba(255,255,255,0.4); margin-top: 20px; }
        .footer { text-align: center; padding-top: 28px; font-size: 12px; color: rgba(255,255,255,0.25); line-height: 1.6; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="logo">{{ $appName }}</div>

        <div class="card">
            <div class="card-header">
                <h1>Password Reset Request</h1>
                <p>We received a request to reset the password for your account.</p>
            </div>
            <div class="card-body">
                <p>Hello,</p>
                <p>
                    Someone requested a password reset for the account associated with
                    <strong style="color:#fff;">{{ $email }}</strong>.
                    If this was you, click the button below to set a new password.
                </p>

                <div class="btn-wrap">
                    <a href="{{ $url }}" class="btn">Reset Password</a>
                </div>

                <p class="expire-note">
                    This link will expire in <strong style="color:rgba(255,255,255,0.65);">{{ $expireMinutes }} minutes</strong>.
                    If you did not request a password reset, no action is required.
                </p>

                <hr class="divider" />

                <p class="link-fallback">
                    If the button above doesn't work, copy and paste this link into your browser:<br />
                    <a href="{{ $url }}">{{ $url }}</a>
                </p>
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.<br />
            You are receiving this email because a password reset was requested for your account.
        </div>
    </div>
</body>
</html>
