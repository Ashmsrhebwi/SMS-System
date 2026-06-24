<x-app-layout>
@section('page-title', 'Segments')
@section('page-subtitle', 'Dynamic contact segments for targeted campaigns')

<div class="max-w-5xl mx-auto">

    <div class="flex items-center justify-between mb-7">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Segments</h2>
            <p class="text-sm text-slate-500 mt-0.5">Create smart contact groups based on tags, opt-in status, and more.</p>
        </div>
        <a href="{{ route('segments.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            New Segment
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
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70">
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Segment</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Conditions</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Eligible</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Campaigns</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($segments as $segment)
                    <tr class="hover:bg-slate-50/50 transition-colors even:bg-slate-50/20">
                        <td class="px-6 py-4">
                            <p class="text-sm font-semibold text-slate-900">{{ $segment->name }}</p>
                            @if($segment->description)
                                <p class="text-xs text-slate-400 mt-0.5">{{ $segment->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if(count($segment->conditions) > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($segment->conditions as $condition)
                                        <span class="inline-flex text-xs bg-slate-100 text-slate-600 rounded-full px-2 py-0.5 font-medium">
                                            {{ str_replace('_', ' ', $condition['field']) }}: {{ $condition['value'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-slate-400">All opted-in</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-semibold text-emerald-700">{{ number_format($segment->eligible_count) }}</span>
                            <span class="text-xs text-slate-400 ml-1">contacts</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-600">{{ number_format($segment->campaigns_count) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2 justify-end">
                                <a href="{{ route('segments.edit', $segment) }}"
                                   class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg transition-colors">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('segments.destroy', $segment) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            onclick="return confirm('Delete segment \'{{ addslashes($segment->name) }}\'?')"
                                            class="inline-flex items-center text-xs font-semibold text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 px-2.5 py-1.5 rounded-lg transition-colors">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-slate-700">No segments yet</p>
                            <p class="text-xs text-slate-400 mt-1 mb-4">Create segments like "VIP Patients" or "Implant Patients" for targeted campaigns.</p>
                            <a href="{{ route('segments.create') }}"
                               class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors">
                                Create First Segment
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</x-app-layout>
