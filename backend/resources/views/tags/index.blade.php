<x-app-layout>
@section('page-title', 'Tags')
@section('page-subtitle', 'Manage contact tags for filtering and segmentation')

<div class="max-w-4xl mx-auto">

    <div class="flex items-center justify-between mb-7">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Tags</h2>
            <p class="text-sm text-slate-500 mt-0.5">Organise contacts with labels like VIP, Implant, Whitening.</p>
        </div>
        <a href="{{ route('tags.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            New Tag
        </a>
    </div>

    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
            <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        @forelse($tags as $tag)
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-50 last:border-0 hover:bg-slate-50/60 transition-colors">
            <div class="flex items-center gap-3">
                <span class="w-4 h-4 rounded-full flex-shrink-0" style="background-color: {{ $tag->color }}"></span>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $tag->name }}</p>
                    <p class="text-xs text-slate-400">{{ number_format($tag->contacts_count) }} contact{{ $tag->contacts_count !== 1 ? 's' : '' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tags.edit', $tag) }}"
                   class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
                <form method="POST" action="{{ route('tags.destroy', $tag) }}" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit"
                            onclick="return confirm('Delete tag \'{{ addslashes($tag->name) }}\'? This will remove it from all contacts.')"
                            class="inline-flex items-center text-xs font-semibold text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 px-2.5 py-1.5 rounded-lg transition-colors">
                        Delete
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="px-6 py-16 text-center">
            <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-slate-700">No tags yet</p>
            <p class="text-xs text-slate-400 mt-1 mb-4">Create tags like VIP, Implant, or Whitening to organise contacts.</p>
            <a href="{{ route('tags.create') }}"
               class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors">
                Create First Tag
            </a>
        </div>
        @endforelse
    </div>

</div>
</x-app-layout>
