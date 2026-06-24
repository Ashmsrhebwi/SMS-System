<x-app-layout>
@section('page-title', 'Contacts')
@section('page-subtitle', 'Manage your patient contact list')

<div class="max-w-7xl mx-auto">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-7">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Contacts</h2>
            <p class="text-sm text-slate-500 mt-0.5">All registered patients and their opt-in status.</p>
        </div>
        <div class="flex items-center gap-2">
            @can('export', \App\Models\Contact::class)
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="inline-flex items-center gap-2 border border-slate-200 bg-white text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export
                </button>
                <div x-show="open" @click.away="open = false"
                     class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-lg border border-slate-200 py-2 z-10">
                    <a href="{{ route('contacts.export', ['filter' => 'all', 'format' => 'xlsx']) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">All Contacts (XLSX)</a>
                    <a href="{{ route('contacts.export', ['filter' => 'all', 'format' => 'csv']) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">All Contacts (CSV)</a>
                    <a href="{{ route('contacts.export', ['filter' => 'opted_in', 'format' => 'xlsx']) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Opted In Only (XLSX)</a>
                    <a href="{{ route('contacts.export', ['filter' => 'opted_in', 'format' => 'csv']) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Opted In Only (CSV)</a>
                </div>
            </div>
            @endcan
            @can('import', \App\Models\Contact::class)
            <a href="{{ route('contacts.import') }}"
               class="inline-flex items-center gap-2 border border-slate-200 bg-white text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Import
            </a>
            @endcan
            @can('create', \App\Models\Contact::class)
            <a href="{{ route('contacts.create') }}"
               class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Add Contact
            </a>
            @endcan
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="mb-5 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-4 rounded-xl text-sm">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="font-semibold">Import complete:
                    <span class="text-emerald-700">{{ $result['imported'] }} imported</span>
                    @if(!empty($result['updated']) && $result['updated'] > 0)
                        &middot; <span class="text-blue-700">{{ $result['updated'] }} updated</span>
                    @endif
                    &middot; <span class="text-amber-700">{{ $result['skipped'] }} skipped</span>
                </p>
            </div>
            @if(!empty($result['errors']))
                <details class="mt-2">
                    <summary class="cursor-pointer text-xs font-medium text-blue-700 hover:text-blue-900">Show {{ count($result['errors']) }} issue(s)</summary>
                    <ul class="mt-2 space-y-0.5 max-h-32 overflow-y-auto">
                        @foreach(array_slice($result['errors'], 0, 20) as $error)
                            <li class="text-xs text-blue-700 pl-2 border-l-2 border-blue-200">{{ $error }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    @endif

    <!-- Filters -->
    <form method="GET" action="{{ route('contacts.index') }}" class="mb-5">
        <div class="flex items-center gap-3 flex-wrap">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white shadow-sm"
                       placeholder="Search name, phone or email…">
            </div>
            @if($tags->count())
            <select name="tag"
                    class="border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-700 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Tags</option>
                @foreach($tags as $tag)
                    <option value="{{ $tag->id }}" {{ request('tag') == $tag->id ? 'selected' : '' }}>{{ $tag->name }}</option>
                @endforeach
            </select>
            @endif
            <select name="opt_in"
                    class="border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-700 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Opt-in Status</option>
                <option value="1" {{ request('opt_in') === '1' ? 'selected' : '' }}>Opted In</option>
                <option value="0" {{ request('opt_in') === '0' ? 'selected' : '' }}>Opted Out</option>
            </select>
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-white border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-colors shadow-sm">
                Filter
            </button>
            @if(request()->hasAny(['search', 'tag', 'opt_in']))
                <a href="{{ route('contacts.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Clear</a>
            @endif
        </div>
    </form>

    <!-- Contacts Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70">
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tags</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Opt-In</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Added</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($contacts as $contact)
                    <tr class="hover:bg-slate-50/50 transition-colors even:bg-slate-50/20">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold
                                    {{ $contact->opted_in ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ strtoupper(substr($contact->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $contact->name)[1] ?? '', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $contact->name }}</p>
                                    @if($contact->email)
                                        <p class="text-xs text-slate-400">{{ $contact->email }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <x-phone :phone="$contact->phone" />
                        </td>
                        <td class="px-6 py-4">
                            @if($contact->tags->count())
                                <div class="flex flex-wrap gap-1">
                                    @foreach($contact->tags as $tag)
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-slate-700 bg-slate-100 rounded-full px-2 py-0.5">
                                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $tag->color }}"></span>
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-slate-300 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @can('toggleOptIn', $contact)
                            <form method="POST" action="{{ route('contacts.toggle-opt-in', $contact) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 transition-colors cursor-pointer
                                        {{ $contact->opted_in
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100'
                                            : 'bg-slate-100 text-slate-500 ring-slate-200 hover:bg-slate-200' }}">
                                    @if($contact->opted_in)
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Opted In
                                    @else
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Opted Out
                                    @endif
                                </button>
                            </form>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1
                                {{ $contact->opted_in ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200' }}">
                                {{ $contact->opted_in ? 'Opted In' : 'Opted Out' }}
                            </span>
                            @endcan
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ $contact->created_at->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center gap-1.5 justify-end">
                                <a href="{{ route('contacts.show', $contact) }}"
                                   class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 px-2.5 py-1.5 rounded-lg transition-colors">
                                    View
                                </a>
                                @can('update', $contact)
                                <a href="{{ route('contacts.edit', $contact) }}"
                                   class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg transition-colors">
                                    Edit
                                </a>
                                @endcan
                                @can('delete', $contact)
                                <form method="POST" action="{{ route('contacts.destroy', $contact) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        onclick="return confirm('Delete {{ addslashes($contact->name) }}?')"
                                        class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-red-600 transition-colors px-2 py-1.5 rounded-lg hover:bg-red-50">
                                        Delete
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center">
                                    <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">No contacts found</p>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        @if(request()->hasAny(['search', 'tag', 'opt_in']))
                                            Try adjusting your filters
                                        @else
                                            Import your patient list or add contacts manually
                                        @endif
                                    </p>
                                </div>
                                @can('create', \App\Models\Contact::class)
                                <a href="{{ route('contacts.create') }}"
                                   class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors mt-1">
                                    Add Contact
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($contacts->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $contacts->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
