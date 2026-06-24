<x-app-layout>
@section('page-title', 'Dashboard')
@section('page-subtitle', 'FeRa Clinic — SMS Campaign Overview')

<div class="max-w-7xl mx-auto">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Overview</h2>
            <p class="text-sm text-slate-500 mt-0.5">Welcome back. Here is what is happening today.</p>
        </div>
        <a href="{{ route('campaigns.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-sm shadow-indigo-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            New Campaign
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center ring-1 ring-blue-100">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-slate-900 leading-none mb-1">{{ number_format($stats['total_contacts']) }}</p>
            <p class="text-sm font-medium text-slate-500">Total Contacts</p>
            <p class="text-xs text-emerald-600 font-semibold mt-1">{{ number_format($stats['opted_in']) }} opted in</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 bg-red-50 rounded-xl flex items-center justify-center ring-1 ring-red-100">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-slate-900 leading-none mb-1">{{ number_format($stats['blacklisted']) }}</p>
            <p class="text-sm font-medium text-slate-500">Blacklisted</p>
            <p class="text-xs text-slate-400 mt-1">Global opt-out list</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 bg-indigo-50 rounded-xl flex items-center justify-center ring-1 ring-indigo-100">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-slate-900 leading-none mb-1">{{ number_format($stats['total_messages_sent']) }}</p>
            <p class="text-sm font-medium text-slate-500">Messages Sent</p>
            <p class="text-xs text-slate-400 mt-1">{{ $stats['active_campaigns'] }} active campaign{{ $stats['active_campaigns'] !== 1 ? 's' : '' }}</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 bg-emerald-50 rounded-xl flex items-center justify-center ring-1 ring-emerald-100">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-slate-900 leading-none mb-1">{{ number_format($stats['total_delivered']) }}</p>
            <p class="text-sm font-medium text-slate-500">Delivered</p>
            <div class="flex items-center gap-2 mt-1">
                <p class="text-xs text-emerald-600 font-semibold">{{ $stats['delivery_rate'] }}% delivery</p>
                <span class="text-slate-300">·</span>
                <p class="text-xs text-violet-600 font-semibold">{{ $stats['click_rate'] }}% click</p>
            </div>
        </div>

    </div>

    <!-- Cost Widgets -->
    @if(auth()->user()->isAdmin())
    <div class="grid grid-cols-3 gap-5 mb-8">
        @foreach([
            ['label' => 'Cost Today',     'value' => $stats['cost_today'],      'icon' => 'clock',    'color' => 'indigo'],
            ['label' => 'Cost This Month','value' => $stats['cost_this_month'], 'icon' => 'calendar', 'color' => 'violet'],
            ['label' => 'Total Cost',     'value' => $stats['cost_total'],      'icon' => 'currency', 'color' => 'emerald'],
        ] as $card)
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-4">
                <div class="w-11 h-11 bg-{{ $card['color'] }}-50 rounded-xl flex items-center justify-center ring-1 ring-{{ $card['color'] }}-100">
                    <svg class="w-5 h-5 text-{{ $card['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($card['icon'] === 'currency')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1c-1.11 0-2.08.402-2.599 1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @elseif($card['icon'] === 'clock')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        @endif
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-slate-900 leading-none mb-1">{{ $card['value'] }}</p>
            <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
            <a href="{{ route('reports.costs') }}" class="text-xs text-{{ $card['color'] }}-600 font-semibold mt-1 inline-block hover:underline">View report →</a>
        </div>
        @endforeach
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <!-- Recent Campaigns -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Recent Campaigns</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Latest {{ count($recentCampaigns) }} campaigns</p>
                </div>
                <a href="{{ route('campaigns.index') }}"
                   class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-800 font-semibold transition-colors">
                    View all
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            @forelse($recentCampaigns as $campaign)
            <div class="px-6 py-4 flex items-center justify-between border-b border-slate-50 last:border-0 hover:bg-slate-50/60 transition-colors">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-9 h-9 bg-indigo-50 rounded-xl flex items-center justify-center flex-shrink-0 ring-1 ring-indigo-100">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 text-sm truncate">{{ $campaign->name }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ $campaign->created_at->format('d M Y') }}
                            <span class="mx-1 text-slate-300">&middot;</span>
                            {{ number_format($campaign->total_recipients) }} recipients
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-4 flex-shrink-0 ml-4">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                        @if($campaign->status === 'completed') bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200
                        @elseif($campaign->status === 'sending') bg-blue-50 text-blue-700 ring-1 ring-blue-200
                        @elseif($campaign->status === 'scheduled') bg-amber-50 text-amber-700 ring-1 ring-amber-200
                        @elseif($campaign->status === 'draft') bg-slate-100 text-slate-600 ring-1 ring-slate-200
                        @else bg-red-50 text-red-700 ring-1 ring-red-200 @endif">
                        {{ ucfirst($campaign->status) }}
                    </span>
                    <a href="{{ route('campaigns.show', $campaign) }}"
                       class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                        View
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
            @empty
            <div class="px-6 py-12 text-center">
                <p class="text-sm font-semibold text-slate-700 mb-1">No campaigns yet</p>
                <p class="text-xs text-slate-400 mb-5">Create your first SMS campaign to get started.</p>
                <a href="{{ route('campaigns.create') }}"
                   class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm">
                    Create Campaign
                </a>
            </div>
            @endforelse
        </div>

        <!-- Recent Activity + Top Countries -->
        <div class="space-y-5">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="text-base font-semibold text-slate-900">Recent Activity</h3>
                <p class="text-xs text-slate-400 mt-0.5">Latest message events</p>
            </div>

            <div class="divide-y divide-slate-50">
                @forelse($recentActivity as $msg)
                <div class="px-5 py-3.5 flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5
                        @if($msg->status === 'delivered') bg-emerald-100
                        @elseif(in_array($msg->status, ['failed','undelivered'])) bg-red-100
                        @else bg-slate-100 @endif">
                        @if($msg->status === 'delivered')
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-slate-800 truncate">{{ $msg->contact?->name ?? '[Deleted]' }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ $msg->campaign?->name ?? '—' }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $msg->updated_at->diffForHumans() }}</p>
                    </div>
                    <span class="text-xs font-medium px-1.5 py-0.5 rounded-full flex-shrink-0
                        @if($msg->status === 'delivered') text-emerald-700 bg-emerald-50
                        @else text-red-600 bg-red-50 @endif">
                        {{ ucfirst($msg->status) }}
                    </span>
                </div>
                @empty
                <div class="px-6 py-10 text-center">
                    <p class="text-xs text-slate-400">No recent activity</p>
                </div>
                @endforelse
            </div>
        </div>

        @if(auth()->user()->isAdmin() && count($topCountries) > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-900">Top Countries</h3>
                <a href="{{ route('reports.countries') }}" class="text-xs text-indigo-600 font-semibold hover:underline">View all →</a>
            </div>
            <div class="space-y-2.5">
                @foreach($topCountries as $c)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-700">{{ $c['country'] }}</span>
                    <span class="text-sm font-semibold text-slate-900">{{ number_format($c['count']) }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        </div> {{-- end space-y-5 --}}

    </div>

</div>
</x-app-layout>
