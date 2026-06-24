<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Login Code</title>
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
  .greeting { font-size: 15px; color: #475569; margin-bottom: 24px; }
  .otp-label { font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 12px; }
  .otp-box { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 24px; }
  .otp-code { font-size: 42px; font-weight: 800; letter-spacing: .3em; color: #1e1b4b; font-family: 'Courier New', monospace; }
  .expiry-notice { display: flex; align-items: flex-start; gap: 10px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; }
  .expiry-icon { color: #f97316; flex-shrink: 0; margin-top: 1px; }
  .expiry-text { font-size: 13px; color: #9a3412; line-height: 1.5; }
  .divider { border: none; border-top: 1px solid #f1f5f9; margin: 24px 0; }
  .security-note { font-size: 12px; color: #94a3b8; line-height: 1.6; }
  .footer { background: #f8fafc; border-top: 1px solid #f1f5f9; padding: 20px 40px; text-align: center; }
  .footer-text { font-size: 11px; color: #94a3b8; line-height: 1.6; }
  .footer-text a { color: #6366f1; text-decoration: none; }
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
      <p class="greeting">Hello <strong>{{ $userName }}</strong>,</p>
      <p style="font-size:15px;color:#475569;margin-bottom:24px;line-height:1.6;">
        Use the code below to complete your sign-in to FeRa Clinic SMS Platform.
      </p>

      <p class="otp-label">Your one-time code</p>
      <div class="otp-box">
        <div class="otp-code">{{ $otp }}</div>
      </div>

      <div class="expiry-notice">
        <span class="expiry-icon">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </span>
        <span class="expiry-text">
          This code expires in <strong>{{ $expiry }} minutes</strong>. Do not share it with anyone.
        </span>
      </div>

      <hr class="divider">

      <p class="security-note">
        If you did not attempt to log in to FeRa Clinic, please ignore this email and consider changing your password immediately. This is an automated security email — please do not reply.
      </p>
    </div>

    <div class="footer">
      <p class="footer-text">
        &copy; {{ date('Y') }} FeRa Clinic &middot; SMS Campaign Platform<br>
        UK GDPR &amp; PECR Compliant &middot; Powered by Twilio
      </p>
    </div>
  </div>
</div>
</body>
</html>
