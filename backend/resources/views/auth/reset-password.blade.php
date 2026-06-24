<x-guest-layout>
    <div class="mb-8">
        <div class="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-900">Set new password</h2>
        <p class="text-sm text-slate-500 mt-1">Choose a strong password for your account.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">
                Email Address
            </label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                   required autofocus autocomplete="username"
                   class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow">
            @error('email')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">
                New Password
            </label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                   placeholder="••••••••••••">
            @error('password')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">
                Confirm Password
            </label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   required autocomplete="new-password"
                   class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                   placeholder="••••••••••••">
            @error('password_confirmation')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password rules hint -->
        <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-xs text-slate-500 space-y-1">
            <p class="font-semibold text-slate-600 mb-1">Password requirements:</p>
            <p>✓ Minimum 12 characters</p>
            <p>✓ Uppercase and lowercase letters</p>
            <p>✓ At least one number</p>
            <p>✓ At least one special character</p>
        </div>

        <button type="submit"
                class="w-full bg-indigo-600 text-white py-2.5 px-4 rounded-xl text-sm font-semibold hover:bg-indigo-700
                       active:bg-indigo-800 transition-colors shadow-sm shadow-indigo-200">
            Reset Password
        </button>
    </form>
</x-guest-layout>
