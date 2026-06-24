<x-app-layout>
@section('page-title', 'Edit Tag')
@section('page-subtitle', 'Update tag name and colour')

<div class="max-w-lg mx-auto">

    <div class="flex items-center gap-4 mb-7">
        <a href="{{ route('tags.index') }}"
           class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Tag</h2>
    </div>

    @if($errors->any())
        <div class="mb-5 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tags.update', $tag) }}">
        @csrf @method('PUT')
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-5">

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="name">Tag Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $tag->name) }}" required maxlength="100"
                       class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Colour <span class="text-red-500">*</span></label>
                <div class="flex flex-wrap gap-2" x-data="{ selected: '{{ old('color', $tag->color) }}' }">
                    @php
                        $colours = ['#6366f1','#8b5cf6','#ec4899','#ef4444','#f97316','#eab308','#22c55e','#06b6d4','#3b82f6','#64748b'];
                    @endphp
                    @foreach($colours as $colour)
                    <button type="button"
                            @click="selected = '{{ $colour }}'"
                            :class="selected === '{{ $colour }}' ? 'ring-2 ring-offset-2 ring-slate-400 scale-110' : ''"
                            class="w-8 h-8 rounded-full transition-all flex-shrink-0"
                            style="background-color: {{ $colour }}"></button>
                    @endforeach
                    <input type="hidden" name="color" :value="selected">
                </div>
            </div>

        </div>

        <div class="flex items-center gap-3 mt-5">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
                Save Changes
            </button>
            <a href="{{ route('tags.index') }}"
               class="px-6 py-2.5 rounded-xl text-sm font-semibold text-slate-600 border border-slate-200 bg-white hover:bg-slate-50 transition-colors shadow-sm">
                Cancel
            </a>
        </div>
    </form>
</div>
</x-app-layout>
