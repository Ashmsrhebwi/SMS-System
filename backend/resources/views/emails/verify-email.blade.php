@extends('emails.layout')

@section('title', 'Verify Your Email – FeRa Clinic')

@section('content')
<p class="greeting">Welcome to FeRa Clinic, {{ $user->name }}!</p>
<p class="text">
  Thanks for joining the FeRa Clinic SMS Platform. To complete your account setup and start using the platform, please verify your email address.
</p>

<!-- Verify Button -->
<div style="text-align:center;margin:32px 0;">
  <a href="{{ $verificationUrl }}"
     style="display:inline-block;background:linear-gradient(135deg,#059669,#10b981);color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:12px;letter-spacing:0.3px;">
    Verify Email Address
  </a>
</div>

<p class="text" style="text-align:center;font-size:13px;color:#64748b;">
  Or copy and paste this link into your browser:
</p>
<p style="word-break:break-all;font-size:12px;color:#059669;text-align:center;margin-bottom:20px;">
  {{ $verificationUrl }}
</p>

<div class="divider"></div>

<p style="font-size:13px;color:#64748b;line-height:1.6;">
  This verification link will expire in <strong>60 minutes</strong>. If you need a new link, you can request one from the login page after signing in.
</p>

<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 18px;margin:20px 0;">
  <p style="font-size:13px;color:#166534;margin:0;line-height:1.5;">
    If you did not create an account with FeRa Clinic, please disregard this email.
    No account will be activated without clicking the verification link.
  </p>
</div>
@endsection
