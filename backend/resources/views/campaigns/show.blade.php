<x-app-layout>
@section('page-title', $campaign->name)
@section('page-subtitle', 'Campaign details and delivery analytics')

<div class="max-w-7xl mx-auto">

    <!-- Page Header -->
    <div class="flex items-start justify-between mb-7">
        <div class="flex items-start gap-4">
            <a href="{{ route('campaigns.index') }}"
               class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors shadow-sm flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $campaign->name }}</h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                        @if($campaign->status === 'completed') bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200
                        @elseif($campaign->status === 'sending') bg-blue-50 text-blue-700 ring-1 ring-blue-200
                        @elseif($campaign->status === 'scheduled') bg-amber-50 text-amber-700 ring-1 ring-amber-200
                        @elseif($campaign->status === 'draft') bg-slate-100 text-slate-600 ring-1 ring-slate-200
                        @else bg-red-50 text-red-700 ring-1 ring-red-200 @endif">
                        @if($campaign->status === 'sending')
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5 animate-pulse"></span>
                        @endif
                        {{ ucfirst($campaign->status) }}
                    </span>
                    @if($campaign->segment)
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 bg-slate-100 rounded-full px-2.5 py-1">
                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                            {{ $campaign->segment->name }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    Created {{ $campaign->created_at->format('d M Y H:i') }}
                    @if($campaign->scheduled_at)
                        <span class="mx-1.5 text-slate-300">&middot;</span>
                        Scheduled: {{ $campaign->scheduled_at->format('d M Y H:i') }}
                    @endif
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            @can('duplicate', $campaign)
            <form method="POST" action="{{ route('campaigns.duplicate', $campaign) }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 border border-slate-200 bg-white text-slate-600 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Clone
                </button>
            </form>
            @endcan
            @can('send', $campaign)
            @if(in_array($campaign->status, ['draft', 'scheduled']))
                <form method="POST" action="{{ route('campaigns.send-now', $campaign) }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm"
                            onclick="return confirm('Send now to all eligible contacts?')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Send Now
                    </button>
                </form>
            @endif
            @endcan
            @can('export', $campaign)
            <a href="{{ route('campaigns.export', $campaign) }}"
               class="inline-flex items-center gap-2 border border-slate-200 bg-white text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export
            </a>
            @endcan
        </div>
    </div>

    <!-- Flash Message -->
    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Message Template Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-5 h-5 bg-slate-100 rounded-md flex items-center justify-center">
                <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Message Template</p>
        </div>
        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
            <p class="text-sm text-slate-800 font-mono leading-relaxed whitespace-pre-line">{!! preg_replace('/(\{[a-z_]+\})/', '<span class="text-indigo-600 font-semibold not-italic">$1</span>', e($campaign->message_body)) !!}</p>
        </div>
    </div>

    <!-- 7 Mini Stat Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-center hover:shadow-md transition-shadow">
            <p class="text-2xl font-bold text-slate-900 leading-none">{{ number_format($stats['total']) }}</p>
            <p class="text-xs text-slate-500 mt-1.5 font-medium">Total</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-center hover:shadow-md transition-shadow">
            <p class="text-2xl font-bold text-amber-600 leading-none">{{ number_format($stats['pending']) }}</p>
            <p class="text-xs text-slate-500 mt-1.5 font-medium">Pending</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-center hover:shadow-md transition-shadow">
            <p class="text-2xl font-bold text-sky-600 leading-none">{{ number_format($stats['queued']) }}</p>
            <p class="text-xs text-slate-500 mt-1.5 font-medium">Queued</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-center hover:shadow-md transition-shadow">
            <p class="text-2xl font-bold text-indigo-600 leading-none">{{ number_format($stats['sent']) }}</p>
            <p class="text-xs text-slate-500 mt-1.5 font-medium">Sent</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-center hover:shadow-md transition-shadow">
            <p class="text-2xl font-bold text-emerald-600 leading-none">{{ number_format($stats['delivered']) }}</p>
            <p class="text-xs text-slate-500 mt-1.5 font-medium">Delivered</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-center hover:shadow-md transition-shadow">
            <p class="text-2xl font-bold text-red-500 leading-none">{{ number_format($stats['failed']) }}</p>
            <p class="text-xs text-slate-500 mt-1.5 font-medium">Failed</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-center hover:shadow-md transition-shadow">
            <p class="text-2xl font-bold text-violet-600 leading-none">{{ number_format($stats['clicked']) }}</p>
            <p class="text-xs text-slate-500 mt-1.5 font-medium">Clicked</p>
        </div>
    </div>

    <!-- Rate Cards -->
    @if($stats['total'] > 0)
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @php
            $rateCards = [
                ['label' => 'Delivery Rate', 'sub' => 'Delivered vs. sent', 'value' => $rates['delivery'], 'color' => 'emerald', 'bar' => 'bg-emerald-500'],
                ['label' => 'Failure Rate', 'sub' => 'Failed vs. sent', 'value' => $rates['failure'], 'color' => 'red', 'bar' => 'bg-red-400'],
                ['label' => 'Click Rate', 'sub' => 'Clicked vs. delivered', 'value' => $rates['click'], 'color' => 'violet', 'bar' => 'bg-violet-500'],
                ['label' => 'Opt-Out Rate', 'sub' => 'Opted out after receive', 'value' => $rates['opt_out'], 'color' => 'amber', 'bar' => 'bg-amber-400'],
            ];
        @endphp
        @foreach($rateCards as $card)
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="text-sm font-semibold text-slate-700">{{ $card['label'] }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $card['sub'] }}</p>
                </div>
                <span class="text-xl font-bold text-{{ $card['color'] }}-600">{{ $card['value'] }}%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5">
                <div class="{{ $card['bar'] }} h-2.5 rounded-full transition-all duration-500"
                     style="width: {{ min($card['value'], 100) }}%"></div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Cost + Resend Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Cost</p>
            <p class="text-2xl font-bold text-slate-900">{{ $costStats['symbol'] }}{{ number_format($costStats['total_cost'], 2) }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ number_format($costStats['total_segments']) }} segments total</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Cost per Message</p>
            @php $cpMsg = $stats['total'] > 0 ? round($costStats['total_cost'] / $stats['total'], 4) : 0; @endphp
            <p class="text-2xl font-bold text-slate-900">{{ $costStats['symbol'] }}{{ number_format($cpMsg, 4) }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Average across all recipients</p>
        </div>
        @can('send', $campaign)
        @if($stats['failed'] > 0 && in_array($campaign->status, ['completed', 'sending']))
        <div class="bg-red-50 rounded-2xl p-5 shadow-sm border border-red-100 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-red-800">{{ number_format($stats['failed']) }} Failed Messages</p>
                <p class="text-xs text-red-600 mt-0.5">Ready to be re-queued</p>
            </div>
            <form method="POST" action="{{ route('campaigns.resend-failed', $campaign) }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition-colors shadow-sm"
                        onclick="return confirm('Resend all failed messages?')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Resend Failed
                </button>
            </form>
        </div>
        @endif
        @endcan
    </div>

    <!-- Messages Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Message Details</h3>
                <p class="text-xs text-slate-400 mt-0.5">Individual delivery status per contact</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Contact</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Phone</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Error</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Sent At</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Delivered At</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Clicked</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($messages as $message)
                    <tr class="hover:bg-slate-50/50 transition-colors even:bg-slate-50/20">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-indigo-700">{{ strtoupper(substr($message->contact?->name ?? '?', 0, 2)) }}</span>
                                </div>
                                <span class="text-sm font-semibold text-slate-900">{{ $message->contact?->name ?? '[Deleted]' }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($message->contact)
                                <x-phone :phone="$message->contact->phone" class="text-xs" />
                            @else
                                <span class="text-slate-300 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                @if($message->status === 'delivered') bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200
                                @elseif($message->status === 'sent') bg-blue-50 text-blue-700 ring-1 ring-blue-200
                                @elseif($message->status === 'queued') bg-amber-50 text-amber-700 ring-1 ring-amber-200
                                @elseif(in_array($message->status, ['failed','undelivered'])) bg-red-50 text-red-700 ring-1 ring-red-200
                                @else bg-slate-100 text-slate-600 ring-1 ring-slate-200 @endif">
                                {{ ucfirst($message->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($message->error_code)
                                <span class="text-xs text-red-600 font-medium">{{ $message->error_code }}: {{ Str::limit($message->error_message, 35) }}</span>
                            @else
                                <span class="text-slate-300 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="text-xs text-slate-500">{{ $message->sent_at?->format('d M H:i') ?? '—' }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="text-xs text-slate-500">{{ $message->delivered_at?->format('d M H:i') ?? '—' }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($message->click?->click_count > 0)
                                <div class="flex items-center gap-1">
                                    <div class="w-4 h-4 bg-violet-100 rounded-full flex items-center justify-center">
                                        <svg class="w-2.5 h-2.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/>
                                        </svg>
                                    </div>
                                    <span class="text-xs text-violet-700 font-semibold">{{ $message->click->click_count }}x</span>
                                    <span class="text-xs text-slate-400">({{ $message->click->first_clicked_at?->format('d M H:i') }})</span>
                                </div>
                            @else
                                <span class="text-slate-300 text-sm">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-slate-600">No messages yet</p>
                            <p class="text-xs text-slate-400 mt-1">Messages will appear once the campaign starts sending.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($messages->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40">
            {{ $messages->links() }}
        </div>
        @endif
    </div>

</div>
</x-app-layout>
