<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900">Welcome back</h2>
        <p class="text-sm text-slate-500 mt-1">Sign in to your FeRa Clinic SMS dashboard</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email -->
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">
                Email Address
            </label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                   placeholder="admin@feraclinic.com">
            @error('email')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide">
                    Password
                </label>
                @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                        Forgot password?
                    </a>
                @endif
            </div>
            <input id="password" type="password" name="password" required
                   class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                   placeholder="••••••••">
            @error('password')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex items-center gap-2">
            <input id="remember_me" type="checkbox" name="remember"
                   class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
            <label for="remember_me" class="text-sm text-slate-600">Keep me signed in</label>
        </div>

        <!-- Submit -->
        <button type="submit"
                class="w-full bg-indigo-600 text-white py-2.5 px-4 rounded-xl text-sm font-semibold hover:bg-indigo-700
                       active:bg-indigo-800 transition-colors shadow-sm shadow-indigo-200 mt-2">
            Sign In
        </button>
    </form>
</x-guest-layout>
