<x-guest-layout>
    <div class="mb-8">
        <!-- Icon -->
        <div class="w-14 h-14 bg-indigo-100 rounded-2xl flex items-center justify-center mb-5">
            <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-900">Two-Step Verification</h2>
        <p class="text-sm text-slate-500 mt-1">
            We sent a 6-digit code to
            <span class="font-semibold text-slate-700">{{ $maskedEmail }}</span>
        </p>
    </div>

    <!-- Status -->
    @if(session('status'))
        <div class="mb-5 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.otp.verify') }}" class="space-y-5">
        @csrf

        <!-- OTP Input -->
        <div>
            <label for="otp" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">
                Verification Code
            </label>
            <input id="otp" type="text" name="otp"
                   inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                   required autofocus autocomplete="one-time-code"
                   class="w-full border border-slate-200 rounded-xl px-3.5 py-3 text-center text-2xl font-bold tracking-[0.6em]
                          text-slate-900 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500
                          focus:border-transparent transition-shadow font-mono"
                   placeholder="––––––"
                   value="{{ old('otp') }}">
            @error('otp')
                <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Submit -->
        <button type="submit"
                class="w-full bg-indigo-600 text-white py-2.5 px-4 rounded-xl text-sm font-semibold hover:bg-indigo-700
                       active:bg-indigo-800 transition-colors shadow-sm shadow-indigo-200">
            Verify &amp; Sign In
        </button>
    </form>

    <!-- Info + Resend -->
    <div class="mt-6 space-y-3">
        <div class="flex items-start gap-2.5 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
            <svg class="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs text-slate-500 leading-relaxed">
                The code expires in <strong class="text-slate-700">5 minutes</strong> and allows
                a maximum of <strong class="text-slate-700">3 attempts</strong>.
            </p>
        </div>

        <div class="flex items-center justify-between text-sm">
            <form method="POST" action="{{ route('login.otp.resend') }}">
                @csrf
                <button type="submit"
                        class="text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                    Resend code
                </button>
            </form>
            <a href="{{ route('login') }}"
               class="text-slate-500 hover:text-slate-700 transition-colors">
                ← Back to sign in
            </a>
        </div>
    </div>

    <!-- Security notice -->
    <div class="mt-6 flex items-start gap-2 text-xs text-slate-400">
        <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <span>
            Never share this code with anyone.
            FeRa Clinic will never ask for your verification code.
        </span>
    </div>
</x-guest-layout>
