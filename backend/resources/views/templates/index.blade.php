<x-app-layout>
@section('page-title', 'SMS Templates')
@section('page-subtitle', 'Reusable message templates for your campaigns')

<div class="max-w-5xl mx-auto">

    <div class="flex items-center justify-between mb-7">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">SMS Templates</h2>
            <p class="text-sm text-slate-500 mt-0.5">Manage reusable messages. Use <code class="text-indigo-600 font-semibold text-xs bg-indigo-50 px-1.5 py-0.5 rounded">{name}</code> and <code class="text-indigo-600 font-semibold text-xs bg-indigo-50 px-1.5 py-0.5 rounded">{tracking_url}</code> as placeholders.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('template-categories.index') }}"
               class="inline-flex items-center gap-2 border border-slate-200 bg-white text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-colors shadow-sm">
                Categories
            </a>
            <a href="{{ route('templates.create') }}"
               class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                New Template
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="space-y-3">
        @forelse($templates as $template)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <h3 class="text-sm font-semibold text-slate-900">{{ $template->name }}</h3>
                        @if($template->category)
                            <span class="inline-flex text-xs font-medium text-indigo-700 bg-indigo-50 rounded-full px-2 py-0.5">{{ $template->category->name }}</span>
                        @endif
                        @if($template->creator)
                            <span class="text-xs text-slate-400">by {{ $template->creator->name }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-600 font-mono leading-relaxed bg-slate-50 rounded-lg p-3 border border-slate-100">{!! preg_replace('/(\{[a-z_]+\})/', '<span class="text-indigo-600 font-semibold">$1</span>', e($template->content)) !!}</p>
                    <p class="text-xs text-slate-400 mt-2">{{ strlen($template->content) }} characters &middot; Created {{ $template->created_at->format('d M Y') }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('templates.edit', $template) }}"
                       class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg transition-colors">
                        Edit
                    </a>
                    <form method="POST" action="{{ route('templates.destroy', $template) }}" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit"
                                onclick="return confirm('Delete template \'{{ addslashes($template->name) }}\'?')"
                                class="inline-flex items-center text-xs font-semibold text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 px-2.5 py-1.5 rounded-lg transition-colors">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 px-6 py-16 text-center">
            <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-slate-700">No templates yet</p>
            <p class="text-xs text-slate-400 mt-1 mb-4">Create reusable message templates for your campaigns.</p>
            <a href="{{ route('templates.create') }}"
               class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors">
                Create First Template
            </a>
        </div>
        @endforelse
    </div>

</div>
</x-app-layout>
