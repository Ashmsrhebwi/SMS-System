<x-app-layout>
@section('page-title', 'Cost Report')
@section('page-subtitle', 'SMS spend analytics by campaign')

<div class="max-w-7xl mx-auto">

    <div class="flex items-center justify-between mb-7">
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Cost Report</h2>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-7">
        @foreach([
            ['label' => 'Cost Today',     'value' => $symbol . number_format($summary['cost_today'], 2),      'color' => 'indigo'],
            ['label' => 'Cost This Month','value' => $symbol . number_format($summary['cost_this_month'], 2), 'color' => 'violet'],
            ['label' => 'Total Cost',     'value' => $symbol . number_format($summary['total_cost'], 2),      'color' => 'emerald'],
            ['label' => 'Total Messages', 'value' => number_format($summary['total_messages']),               'color' => 'blue'],
            ['label' => 'Total Segments', 'value' => number_format($summary['total_segments']),               'color' => 'slate'],
        ] as $card)
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <p class="text-2xl font-bold text-{{ $card['color'] }}-600 leading-none">{{ $card['value'] }}</p>
            <p class="text-xs text-slate-500 mt-1.5 font-medium">{{ $card['label'] }}</p>
        </div>
        @endforeach
    </div>

    <!-- Campaign Cost Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <h3 class="text-base font-semibold text-slate-900">Cost per Campaign</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70">
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Campaign</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Messages</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Segments</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Cost</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Cost/Msg</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($campaigns as $campaign)
                    @php
                        $msgCount = (int) $campaign->message_count;
                        $costPerMsg = $msgCount > 0 ? round($campaign->total_cost / $msgCount, 4) : 0;
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors even:bg-slate-50/20">
                        <td class="px-6 py-4">
                            <a href="{{ route('campaigns.show', $campaign->id) }}"
                               class="text-sm font-semibold text-slate-900 hover:text-indigo-600 transition-colors">
                                {{ $campaign->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                @if($campaign->status === 'completed') bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200
                                @elseif($campaign->status === 'sending')  bg-blue-50 text-blue-700 ring-1 ring-blue-200
                                @elseif($campaign->status === 'draft')    bg-slate-100 text-slate-600 ring-1 ring-slate-200
                                @else bg-amber-50 text-amber-700 ring-1 ring-amber-200 @endif">
                                {{ ucfirst($campaign->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-medium text-slate-700">{{ number_format($msgCount) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-medium text-slate-700">{{ number_format((int) $campaign->total_segments) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-bold text-slate-900">{{ $symbol }}{{ number_format((float) $campaign->total_cost, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-xs text-slate-500">{{ $symbol }}{{ number_format($costPerMsg, 4) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-500">{{ \Carbon\Carbon::parse($campaign->created_at)->format('d M Y') }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <p class="text-sm text-slate-500">No campaign data yet.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($campaigns->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40">
            {{ $campaigns->links() }}
        </div>
        @endif
    </div>

</div>
</x-app-layout>
