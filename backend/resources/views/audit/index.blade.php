<x-app-layout>
@section('page-title', 'Audit Log')
@section('page-subtitle', 'Complete system action history')

<div class="max-w-7xl mx-auto">

    <div class="flex items-center justify-between mb-7">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Audit Log</h2>
            <p class="text-sm text-slate-500 mt-0.5">Track every significant action performed in the system.</p>
        </div>
        <a href="{{ route('audit.export', request()->query()) }}"
           class="inline-flex items-center gap-2 border border-slate-200 bg-white text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('audit.index') }}" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <select name="user_id"
                    class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Users</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
            <input type="text" name="action" value="{{ request('action') }}"
                   class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="Action keyword…">
            <select name="entity_type"
                    class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Entities</option>
                @foreach($entityTypes as $type)
                    <option value="{{ $type }}" {{ request('entity_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <input type="date" name="to" value="{{ request('to') }}"
                   class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex items-center gap-3 mt-3">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['user_id','action','entity_type','from','to']))
            <a href="{{ route('audit.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Clear</a>
            @endif
        </div>
    </form>

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">User</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Entity</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">IP</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Changes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/50 transition-colors even:bg-slate-50/20">
                        <td class="px-5 py-3.5">
                            <p class="text-xs font-medium text-slate-800">{{ $log->created_at->format('d M Y') }}</p>
                            <p class="text-xs text-slate-400">{{ $log->created_at->format('H:i:s') }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="text-sm font-medium text-slate-700">{{ $log->user?->name ?? 'System' }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            @php
                                $badgeColors = [
                                    'red'     => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                                    'emerald' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                    'amber'   => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                                    'indigo'  => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200',
                                    'slate'   => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
                                ];
                                $badgeClass = $badgeColors[$log->badgeColor()] ?? $badgeColors['slate'];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                {{ str_replace('_', ' ', $log->action) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($log->entity_type)
                                <span class="text-xs text-slate-600">{{ $log->entity_type }}</span>
                                @if($log->entity_id)
                                    <span class="text-xs text-slate-400 ml-1">#{{ $log->entity_id }}</span>
                                @endif
                            @else
                                <span class="text-slate-300 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="text-xs font-mono text-slate-500">{{ $log->ip_address ?? '—' }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($log->new_values)
                                <details>
                                    <summary class="text-xs text-indigo-600 cursor-pointer hover:text-indigo-800 font-medium">View</summary>
                                    <div class="mt-1 text-xs text-slate-600 bg-slate-50 rounded-lg p-2 max-w-xs overflow-auto max-h-24">
                                        @foreach($log->new_values as $k => $v)
                                        <div><span class="font-medium">{{ $k }}:</span> {{ is_array($v) ? json_encode($v) : $v }}</div>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                <span class="text-slate-300 text-sm">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <p class="text-sm text-slate-500">No audit log entries found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

</div>
</x-app-layout>
