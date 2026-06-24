<x-app-layout>
@section('page-title', 'New Template')
@section('page-subtitle', 'Create a reusable SMS message template')

<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-4 mb-7">
        <a href="{{ route('templates.index') }}"
           class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">New Template</h2>
    </div>

    @if($errors->any())
        <div class="mb-5 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('templates.store') }}">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-5">

            @if($categories->count())
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="category_id">Category <span class="text-slate-400 font-normal">(optional)</span></label>
                <select id="category_id" name="category_id"
                        class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">No category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="name">Template Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       placeholder="e.g. Appointment Reminder, Whitening Offer">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="content">Message Content <span class="text-red-500">*</span></label>
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center gap-1 text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1">
                        Use <code class="font-mono font-semibold text-indigo-600 mx-0.5">{name}</code> to personalise
                    </span>
                    <span class="inline-flex items-center gap-1 text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1">
                        <code class="font-mono font-semibold text-indigo-600 mx-0.5">{tracking_url}</code> for links
                    </span>
                </div>
                <textarea id="content" name="content" rows="6" required maxlength="1600"
                          class="w-full border border-slate-200 rounded-xl px-3.5 py-3 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                          placeholder="Hi {name}, this is FeRa Clinic. Book your appointment at {tracking_url}">{{ old('content') }}</textarea>
                <p class="text-xs text-slate-400 mt-1">Max 1600 characters</p>
            </div>

        </div>

        <div class="flex items-center gap-3 mt-5">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
                Create Template
            </button>
            <a href="{{ route('templates.index') }}"
               class="px-6 py-2.5 rounded-xl text-sm font-semibold text-slate-600 border border-slate-200 bg-white hover:bg-slate-50 transition-colors shadow-sm">
                Cancel
            </a>
        </div>
    </form>
</div>
</x-app-layout>
