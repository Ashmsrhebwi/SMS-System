<x-guest-layout>
    <div class="mb-8">
        <div class="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-900">Check your email</h2>
        <p class="text-sm text-slate-500 mt-1">
            We sent a 6-digit code to <span class="font-semibold text-slate-700">{{ $maskedEmail }}</span>
        </p>
    </div>

    @if(session('status'))
        <div class="mb-5 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('otp.verify') }}" class="space-y-5">
        @csrf

        <div>
            <label for="otp" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">
                One-Time Code
            </label>
            <input id="otp" type="text" name="otp" inputmode="numeric" pattern="\d{6}"
                   maxlength="6" autofocus autocomplete="one-time-code" required
                   class="w-full border border-slate-200 rounded-xl px-3.5 py-3 text-2xl font-bold tracking-[.5em] text-center text-slate-900
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                   placeholder="000000">
            @error('otp')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2 bg-amber-50 border border-amber-100 rounded-xl px-3.5 py-2.5 text-xs text-amber-700">
            <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            This code expires in <strong class="ml-1">5 minutes</strong>. Maximum 3 attempts.
        </div>

        <button type="submit"
                class="w-full bg-indigo-600 text-white py-2.5 px-4 rounded-xl text-sm font-semibold hover:bg-indigo-700
                       active:bg-indigo-800 transition-colors shadow-sm shadow-indigo-200">
            Verify Code
        </button>
    </form>

    <div class="mt-6 text-center">
        <p class="text-sm text-slate-500">Didn't receive the email?</p>
        <form method="POST" action="{{ route('otp.resend') }}" class="inline mt-1">
            @csrf
            <button type="submit"
                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                Resend code
            </button>
        </form>
        <span class="text-slate-300 mx-2">·</span>
        <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700 transition-colors">
            Back to login
        </a>
    </div>
</x-guest-layout>
