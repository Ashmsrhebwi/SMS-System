<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SecurityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function __construct(private SecurityLogger $securityLogger) {}

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->securityLogger->passwordResetRequest($request->input('email'));

        $status = Password::sendResetLink($request->only('email'));

        // Always return the same message to prevent email enumeration
        return back()->with('status', __('passwords.sent'));
    }
}
