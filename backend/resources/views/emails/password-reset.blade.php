<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Your Password</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; padding: 40px 20px; }
  .wrapper { max-width: 520px; margin: 0 auto; }
  .card { background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
  .header { background: #1e1b4b; padding: 32px 40px; }
  .logo { display: flex; align-items: center; gap: 12px; }
  .logo-icon { width: 40px; height: 40px; background: rgba(129,140,248,.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; }
  .logo-text { color: #fff; font-size: 18px; font-weight: 700; }
  .body { padding: 40px; }
  .heading { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 12px; }
  .text { font-size: 15px; color: #475569; line-height: 1.7; margin-bottom: 20px; }
  .btn { display: block; background: #4f46e5; color: #ffffff !important; text-decoration: none; text-align: center; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600; margin: 28px 0; }
  .rules { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; }
  .rules-title { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
  .rules ul { padding-left: 16px; }
  .rules li { font-size: 13px; color: #475569; line-height: 1.8; }
  .expiry { display: flex; align-items: flex-start; gap: 10px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; }
  .expiry-icon { color: #f97316; flex-shrink: 0; margin-top: 2px; }
  .expiry-text { font-size: 13px; color: #9a3412; line-height: 1.5; }
  .link-fallback { font-size: 12px; color: #94a3b8; line-height: 1.6; word-break: break-all; }
  .link-fallback a { color: #6366f1; }
  .divider { border: none; border-top: 1px solid #f1f5f9; margin: 24px 0; }
  .footer { background: #f8fafc; border-top: 1px solid #f1f5f9; padding: 20px 40px; text-align: center; }
  .footer-text { font-size: 11px; color: #94a3b8; line-height: 1.6; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="card">
    <div class="header">
      <div class="logo">
        <div class="logo-icon">
          <svg width="20" height="20" fill="none" stroke="#a5b4fc" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
          </svg>
        </div>
        <span class="logo-text">FeRa Clinic</span>
      </div>
    </div>

    <div class="body">
      <h1 class="heading">Reset your password</h1>
      <p class="text">Hello <strong>{{ $userName }}</strong>, we received a request to reset the password for your FeRa Clinic account. Click the button below to choose a new password.</p>

      <a href="{{ $resetUrl }}" class="btn">Reset Password</a>

      <div class="rules">
        <p class="rules-title">New password must include</p>
        <ul>
          <li>At least 12 characters</li>
          <li>Uppercase and lowercase letters</li>
          <li>At least one number</li>
          <li>At least one special character (!@#$%^&amp;*)</li>
        </ul>
      </div>

      <div class="expiry">
        <span class="expiry-icon">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </span>
        <span class="expiry-text">
          This link expires in <strong>{{ $expiry }} minutes</strong> and can only be used once.
        </span>
      </div>

      <hr class="divider">

      <p class="link-fallback">
        If the button above does not work, copy and paste this link into your browser:<br>
        <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
      </p>

      <hr class="divider">

      <p style="font-size:12px;color:#94a3b8;line-height:1.6;">
        If you did not request a password reset, no action is required. Your account remains secure.
      </p>
    </div>

    <div class="footer">
      <p class="footer-text">
        &copy; {{ date('Y') }} FeRa Clinic &middot; SMS Campaign Platform<br>
        UK GDPR &amp; PECR Compliant
      </p>
    </div>
  </div>
</div>
</body>
</html>
