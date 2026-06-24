<x-app-layout>
@section('page-title', 'Add User')
@section('page-subtitle', 'Create a new platform user account')

<div class="max-w-lg mx-auto">

    <div class="flex items-center gap-4 mb-7">
        <a href="{{ route('users.index') }}"
           class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Add User</h2>
    </div>

    @if($errors->any())
        <div class="mb-5 bg-red-50 border border-red-200 text-red-800 px-4 py-4 rounded-xl text-sm">
            <p class="font-semibold mb-1">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('users.store') }}">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-5">

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="name">Full Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="email">Email Address <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="password">Password <span class="text-red-500">*</span></label>
                <input type="password" id="password" name="password" required
                       class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       placeholder="Min. 8 characters">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="password_confirmation">Confirm Password <span class="text-red-500">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Role <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    @foreach(['admin' => ['Admin', 'Full access to all features'], 'standard' => ['Standard User', 'Limited access, cannot manage settings']] as $value => $meta)
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="{{ $value }}" {{ old('role', 'standard') === $value ? 'checked' : '' }} class="sr-only peer">
                        <div class="border-2 border-slate-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 rounded-xl p-4 transition-all">
                            <p class="text-sm font-semibold text-slate-800">{{ $meta[0] }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $meta[1] }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

        </div>

        <div class="flex items-center gap-3 mt-5">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
                Create User
            </button>
            <a href="{{ route('users.index') }}"
               class="px-6 py-2.5 rounded-xl text-sm font-semibold text-slate-600 border border-slate-200 bg-white hover:bg-slate-50 transition-colors shadow-sm">
                Cancel
            </a>
        </div>
    </form>
</div>
</x-app-layout>
