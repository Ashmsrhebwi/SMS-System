<x-app-layout>
@section('page-title', $contact->name)
@section('page-subtitle', 'Contact profile, timeline and notes')

<div class="max-w-5xl mx-auto">

    <!-- Page Header -->
    <div class="flex items-start justify-between mb-7">
        <div class="flex items-start gap-4">
            <a href="{{ route('contacts.index') }}"
               class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors shadow-sm flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-lg font-bold ring-2
                        {{ $contact->opted_in ? 'bg-indigo-100 text-indigo-700 ring-indigo-200' : 'bg-slate-100 text-slate-500 ring-slate-200' }}">
                        {{ strtoupper(substr($contact->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $contact->name)[1] ?? '', 0, 1)) }}
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $contact->name }}</h2>
                        <div class="flex items-center gap-2 mt-0.5">
                            <x-phone :phone="$contact->phone" />
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $contact->opted_in ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-red-50 text-red-700 ring-1 ring-red-200' }}">
                                {{ $contact->opted_in ? 'Opted In' : 'Opted Out' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @can('update', $contact)
        <a href="{{ route('contacts.edit', $contact) }}"
           class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm">
            Edit Contact
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Info + Notes + Campaigns -->
        <div class="space-y-5">

            <!-- Contact Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Contact Info</h3>
                <dl class="space-y-3">
                    @if($contact->email)
                    <div>
                        <dt class="text-xs text-slate-400">Email</dt>
                        <dd class="text-sm text-slate-800 font-medium">{{ $contact->email }}</dd>
                    </div>
                    @endif
                    @if($contact->notes)
                    <div>
                        <dt class="text-xs text-slate-400">Notes</dt>
                        <dd class="text-sm text-slate-600">{{ $contact->notes }}</dd>
                    </div>
                    @endif
                    @if($contact->last_visit)
                    <div>
                        <dt class="text-xs text-slate-400">Last Visit</dt>
                        <dd class="text-sm text-slate-800 font-medium">{{ $contact->last_visit->format('d M Y') }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-xs text-slate-400">Added</dt>
                        <dd class="text-sm text-slate-800 font-medium">{{ $contact->created_at->format('d M Y') }}</dd>
                    </div>
                </dl>

                @if($contact->tags->count())
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-xs text-slate-400 mb-2">Tags</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($contact->tags as $tag)
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-slate-700 bg-slate-100 rounded-full px-2.5 py-1">
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $tag->color }}"></span>
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Campaign History -->
            @if($campaigns->count())
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Campaign History</h3>
                <div class="space-y-2.5">
                    @foreach($campaigns as $msg)
                    <div class="flex items-center justify-between">
                        <a href="{{ route('campaigns.show', $msg->campaign_id) }}"
                           class="text-sm font-medium text-slate-700 hover:text-indigo-600 transition-colors truncate flex-1">
                            {{ $msg->campaign?->name ?? 'Campaign #'.$msg->campaign_id }}
                        </a>
                        <span class="ml-2 flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                            @if($msg->status === 'delivered') bg-emerald-50 text-emerald-700
                            @elseif(in_array($msg->status, ['failed','undelivered'])) bg-red-50 text-red-700
                            @else bg-slate-100 text-slate-600 @endif">
                            {{ ucfirst($msg->status) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Notes -->
            @can('update', $contact)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Notes</h3>

                <form method="POST" action="{{ route('contacts.notes.store', $contact) }}" class="mb-4">
                    @csrf
                    <textarea name="note" rows="2" required
                              class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none mb-2"
                              placeholder="Add a note about this contact…"></textarea>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 bg-indigo-600 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-indigo-700 transition-colors">
                        Add Note
                    </button>
                </form>

                @forelse($contact->notes as $note)
                <div class="border-t border-slate-100 py-3 group">
                    <p class="text-sm text-slate-700 leading-relaxed mb-1.5">{{ $note->note }}</p>
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-slate-400">
                            {{ $note->author?->name ?? 'Unknown' }} &middot; {{ $note->created_at->format('d M Y H:i') }}
                        </p>
                        @if($note->user_id === auth()->id() || auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('contacts.notes.destroy', [$contact, $note]) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="text-xs text-slate-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100">
                                Delete
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-xs text-slate-400">No notes yet.</p>
                @endforelse
            </div>
            @endcan

        </div>

        <!-- Right: Activity Timeline -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-base font-semibold text-slate-900 mb-6">Activity Timeline</h3>

                @if($contact->activities->isEmpty())
                <div class="py-12 text-center">
                    <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500">No activity recorded yet.</p>
                </div>
                @else

                @php $groupedActivities = $contact->activities->groupBy(fn($a) => $a->created_at->format('Y-m-d')); @endphp

                <div class="relative">
                    <div class="absolute left-3.5 top-0 bottom-0 w-px bg-slate-100"></div>

                    @foreach($groupedActivities as $date => $dayActivities)
                    <div class="mb-6">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-7 h-7 bg-white rounded-full border-2 border-slate-200 flex items-center justify-center flex-shrink-0 relative z-10">
                                <div class="w-2 h-2 bg-slate-300 rounded-full"></div>
                            </div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                            </p>
                        </div>

                        <div class="space-y-3 pl-10">
                            @foreach($dayActivities as $activity)
                            @php
                                $colorMap = [
                                    'emerald' => 'bg-emerald-100 text-emerald-600',
                                    'red'     => 'bg-red-100 text-red-600',
                                    'amber'   => 'bg-amber-100 text-amber-600',
                                    'violet'  => 'bg-violet-100 text-violet-600',
                                    'indigo'  => 'bg-indigo-100 text-indigo-600',
                                    'sky'     => 'bg-sky-100 text-sky-600',
                                    'slate'   => 'bg-slate-100 text-slate-600',
                                ];
                                $colorClass = $colorMap[$activity->color()] ?? 'bg-slate-100 text-slate-600';
                            @endphp
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 {{ $colorClass }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($activity->event_type === 'contact_created' || $activity->event_type === 'contact_imported')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        @elseif($activity->event_type === 'sms_delivered')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        @elseif($activity->event_type === 'sms_failed')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        @elseif($activity->event_type === 'link_clicked')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/>
                                        @elseif($activity->event_type === 'opted_out')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        @endif
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0 pt-0.5">
                                    <p class="text-sm text-slate-800">{{ $activity->description }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ $activity->created_at->format('H:i') }}
                                        @if($activity->user)
                                            <span class="mx-1 text-slate-300">&middot;</span> {{ $activity->user->name }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

    </div>
</div>
</x-app-layout>
