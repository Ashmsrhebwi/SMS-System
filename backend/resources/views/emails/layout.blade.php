<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>@yield('title', 'FeRa Clinic')</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; }
  .wrapper { background-color: #f1f5f9; padding: 40px 20px; }
  .container { max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
  .header { background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); padding: 32px 40px; text-align: center; }
  .logo-icon { display: inline-block; background: rgba(255,255,255,0.15); border-radius: 12px; padding: 12px; margin-bottom: 12px; }
  .header-title { color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: -0.3px; margin: 0; }
  .header-subtitle { color: rgba(255,255,255,0.75); font-size: 13px; margin-top: 4px; }
  .body { padding: 40px 40px 32px; }
  .greeting { font-size: 16px; color: #0f172a; font-weight: 600; margin-bottom: 12px; }
  .text { font-size: 14px; color: #475569; line-height: 1.7; margin-bottom: 16px; }
  .footer { background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 24px 40px; text-align: center; }
  .footer-text { font-size: 12px; color: #94a3b8; line-height: 1.6; }
  .footer-brand { font-size: 12px; color: #64748b; font-weight: 600; margin-top: 8px; }
  .divider { height: 1px; background-color: #e2e8f0; margin: 24px 0; }
  @media only screen and (max-width: 600px) {
    .body { padding: 28px 24px 24px; }
    .header { padding: 24px; }
    .footer { padding: 20px 24px; }
  }
</style>
</head>
<body>
<div class="wrapper">
  <div class="container">

    <!-- Header -->
    <div class="header">
      <div class="logo-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
      </div>
      <h1 class="header-title">FeRa Clinic</h1>
      <p class="header-subtitle">SMS Platform</p>
    </div>

    <!-- Body -->
    <div class="body">
      @yield('content')
    </div>

    <!-- Footer -->
    <div class="footer">
      <p class="footer-text">
        This email was sent by FeRa Clinic SMS Platform.<br>
        If you did not request this, please ignore this email or contact us at
        <a href="mailto:info@feraclinic.com" style="color:#6366f1;text-decoration:none;">info@feraclinic.com</a>
      </p>
      <p class="footer-brand">© {{ date('Y') }} FeRa Clinic. All rights reserved.</p>
    </div>

  </div>
</div>
</body>
</html>
