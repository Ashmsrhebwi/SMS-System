<x-app-layout>
@section('page-title', 'New Campaign')
@section('page-subtitle', 'Compose and schedule your SMS campaign')

<div class="max-w-6xl mx-auto">

    <!-- Page Header -->
    <div class="flex items-center gap-4 mb-7">
        <a href="{{ route('campaigns.index') }}"
           class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">New Campaign</h2>
            <p class="text-sm text-slate-500 mt-0.5" id="eligibleLabel">
                <span class="inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <strong class="text-emerald-700 font-semibold">{{ number_format($eligibleCount) }}</strong> eligible contacts ready to receive
                </span>
            </p>
        </div>
    </div>

    <!-- Validation Errors -->
    @if($errors->any())
        <div class="mb-6 flex gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-4 rounded-xl text-sm">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="font-semibold mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('campaigns.store') }}" id="campaignForm">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

            <!-- Left Column: Form -->
            <div class="lg:col-span-3 space-y-5">

                <!-- Campaign Name -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <span class="w-5 h-5 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </span>
                        Campaign Details
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="name">Campaign Name</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}"
                                   class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                                   placeholder="e.g. Summer Checkup Reminder">
                        </div>

                        @if($segments->count())
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="segment_id">Send To</label>
                            <select id="segment_id" name="segment_id"
                                    class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white">
                                <option value="">All Eligible Contacts ({{ number_format($eligibleCount) }})</option>
                                @foreach($segments as $segment)
                                    <option value="{{ $segment->id }}" {{ old('segment_id') == $segment->id ? 'selected' : '' }}>
                                        {{ $segment->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @else
                            <input type="hidden" name="segment_id" value="">
                        @endif
                    </div>
                </div>

                <!-- Message Body -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <span class="w-5 h-5 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                        </span>
                        Message Body
                    </h3>

                    @if($templates->count())
                    <div class="mb-3">
                        <select id="templatePicker" onchange="loadTemplate(this)"
                                class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Load from template —</option>
                            @foreach($templates as $tmpl)
                                <option value="{{ $tmpl->id }}" data-content="{{ $tmpl->content }}">{{ $tmpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center gap-1 text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1">
                            Use <code class="font-mono font-semibold text-indigo-600 mx-0.5">{name}</code> to personalise
                        </span>
                        <span class="inline-flex items-center gap-1 text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1">
                            <code class="font-mono font-semibold text-indigo-600 mx-0.5">{tracking_url}</code> for tracked links
                        </span>
                    </div>
                    <textarea name="message_body" id="messageBody" rows="6"
                              class="w-full border border-slate-200 rounded-xl px-3.5 py-3 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow resize-none"
                              placeholder="Hi {name}, this is FeRa Clinic. Book your appointment at {tracking_url}"
                              oninput="updatePreview()">{{ old('message_body') }}</textarea>
                    <div class="flex items-center justify-between mt-2.5 text-xs">
                        <div class="flex items-center gap-3 text-slate-500">
                            <span id="charCount" class="font-medium">0 characters</span>
                            <span class="text-slate-300">|</span>
                            <span id="segmentCount" class="font-medium">1 segment (160 chars)</span>
                        </div>
                        <span id="charWarning" class="text-amber-600 font-semibold hidden">Approaching limit</span>
                    </div>
                </div>

                <!-- Timing Options -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <span class="w-5 h-5 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        When to Send
                    </h3>

                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <label class="timing-card cursor-pointer" for="timing_now">
                            <input type="radio" id="timing_now" name="send_timing" value="now" checked class="sr-only">
                            <div class="timing-option border-2 border-slate-200 rounded-xl p-4 text-center hover:border-indigo-300 transition-all">
                                <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center mx-auto mb-2.5">
                                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <p class="text-xs font-semibold text-slate-700">Send Now</p>
                                <p class="text-xs text-slate-400 mt-0.5">Immediately</p>
                            </div>
                        </label>
                        <label class="timing-card cursor-pointer" for="timing_later">
                            <input type="radio" id="timing_later" name="send_timing" value="later" class="sr-only">
                            <div class="timing-option border-2 border-slate-200 rounded-xl p-4 text-center hover:border-indigo-300 transition-all">
                                <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-2.5">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <p class="text-xs font-semibold text-slate-700">Schedule</p>
                                <p class="text-xs text-slate-400 mt-0.5">Pick a date</p>
                            </div>
                        </label>
                        <label class="timing-card cursor-pointer" for="timing_draft">
                            <input type="radio" id="timing_draft" name="send_timing" value="draft" class="sr-only">
                            <div class="timing-option border-2 border-slate-200 rounded-xl p-4 text-center hover:border-indigo-300 transition-all">
                                <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-2.5">
                                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                </div>
                                <p class="text-xs font-semibold text-slate-700">Save Draft</p>
                                <p class="text-xs text-slate-400 mt-0.5">For later</p>
                            </div>
                        </label>
                    </div>

                    <div id="scheduleInput" class="hidden mt-1">
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">Schedule Date &amp; Time</label>
                        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
                               class="border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow w-full">
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center gap-3">
                    <button type="submit" id="submitBtn"
                            class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-xl text-sm font-semibold hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-sm shadow-indigo-200 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <span id="submitBtnText">Send Campaign</span>
                    </button>
                    <a href="{{ route('campaigns.index') }}"
                       class="px-6 py-3 rounded-xl text-sm font-semibold text-slate-600 border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-sm">
                        Cancel
                    </a>
                </div>

            </div>

            <!-- Right Column: Live Preview -->
            <div class="lg:col-span-2">
                <div class="sticky top-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                        <h3 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                            <span class="w-5 h-5 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </span>
                            Live Preview
                        </h3>

                        <div class="flex justify-center">
                            <div class="relative w-56">
                                <div class="bg-slate-800 rounded-[2.5rem] p-3 shadow-2xl">
                                    <div class="bg-slate-100 rounded-[2rem] overflow-hidden">
                                        <div class="bg-slate-200/80 px-4 py-1.5 flex items-center justify-between">
                                            <span class="text-[10px] font-semibold text-slate-600">9:41</span>
                                            <div class="flex items-center gap-1">
                                                <div class="w-3 h-1.5 bg-slate-600 rounded-sm"></div>
                                                <div class="w-1 h-1 rounded-full bg-slate-600"></div>
                                            </div>
                                        </div>
                                        <div class="bg-slate-100 min-h-[280px] px-3 py-4">
                                            <div class="text-center mb-3">
                                                <span class="text-[10px] text-slate-400 font-medium bg-white/80 rounded-full px-2.5 py-0.5">FeRa Clinic</span>
                                            </div>
                                            <div id="phoneEmpty" class="flex flex-col items-center justify-center py-8 text-center">
                                                <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center mb-2">
                                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                                    </svg>
                                                </div>
                                                <p class="text-xs text-slate-400 leading-snug">Start typing your<br>message to preview</p>
                                            </div>
                                            <div id="phoneBubble" class="hidden">
                                                <div class="bg-white rounded-2xl rounded-bl-sm px-3 py-2.5 shadow-sm max-w-[90%]">
                                                    <p id="previewText" class="text-xs text-slate-800 leading-relaxed break-words"></p>
                                                </div>
                                                <div class="flex justify-end mt-1">
                                                    <span class="text-[10px] text-slate-400">Delivered</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-center mt-2">
                                    <div class="w-20 h-1 bg-slate-600 rounded-full"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-2 gap-3">
                            <div class="bg-slate-50 rounded-xl px-3 py-2.5 text-center">
                                <p class="text-lg font-bold text-slate-900" id="previewCharCount">0</p>
                                <p class="text-xs text-slate-500">Characters</p>
                            </div>
                            <div class="bg-slate-50 rounded-xl px-3 py-2.5 text-center">
                                <p class="text-lg font-bold text-slate-900" id="previewSegCount">1</p>
                                <p class="text-xs text-slate-500">SMS Segments</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 text-center mt-3">
                            160 chars/segment &middot; 153 chars for multi-part
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

<script>
function loadTemplate(select) {
    const option = select.options[select.selectedIndex];
    const content = option.getAttribute('data-content');
    if (content) {
        document.getElementById('messageBody').value = content;
        updatePreview();
    }
}

function updatePreview() {
    const body = document.getElementById('messageBody').value;
    const charCount = document.getElementById('charCount');
    const segmentCount = document.getElementById('segmentCount');
    const previewCharCount = document.getElementById('previewCharCount');
    const previewSegCount = document.getElementById('previewSegCount');
    const phoneBubble = document.getElementById('phoneBubble');
    const phoneEmpty = document.getElementById('phoneEmpty');
    const previewText = document.getElementById('previewText');
    const charWarning = document.getElementById('charWarning');

    const len = body.length;
    charCount.textContent = len + ' characters';
    previewCharCount.textContent = len;

    let segs = 1;
    if (len > 160) segs = Math.ceil(len / 153);
    segmentCount.textContent = segs + ' segment' + (segs > 1 ? 's' : '') + ' (' + (segs === 1 ? '160' : '153') + ' chars/segment)';
    previewSegCount.textContent = segs;

    const singleLimit = segs === 1 ? 160 : segs * 153;
    const remaining = singleLimit - len;
    if (len > 0 && remaining < 20) {
        charWarning.textContent = remaining + ' chars left';
        charWarning.classList.remove('hidden');
    } else {
        charWarning.classList.add('hidden');
    }

    if (len > 0) {
        phoneBubble.classList.remove('hidden');
        phoneEmpty.classList.add('hidden');
        previewText.textContent = body
            .replace(/\{name\}/g, 'John')
            .replace(/\{tracking_url\}/g, 'https://fera.clinic/r/abc123');
    } else {
        phoneBubble.classList.add('hidden');
        phoneEmpty.classList.remove('hidden');
    }
}

function updateTimingCards() {
    const selected = document.querySelector('input[name="send_timing"]:checked');
    document.querySelectorAll('.timing-option').forEach(el => {
        el.classList.remove('border-indigo-500', 'bg-indigo-50');
        el.classList.add('border-slate-200');
    });
    if (selected) {
        const card = selected.closest('.timing-card').querySelector('.timing-option');
        card.classList.remove('border-slate-200');
        card.classList.add('border-indigo-500', 'bg-indigo-50');
    }
}

document.querySelectorAll('input[name="send_timing"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const scheduleInput = document.getElementById('scheduleInput');
        const submitBtnText = document.getElementById('submitBtnText');

        if (this.value === 'later') {
            scheduleInput.classList.remove('hidden');
            submitBtnText.textContent = 'Schedule Campaign';
        } else if (this.value === 'draft') {
            scheduleInput.classList.add('hidden');
            submitBtnText.textContent = 'Save as Draft';
        } else {
            scheduleInput.classList.add('hidden');
            submitBtnText.textContent = 'Send Campaign';
        }
        updateTimingCards();
    });
});

document.getElementById('campaignForm').addEventListener('submit', function(e) {
    const timing = document.querySelector('input[name="send_timing"]:checked').value;
    if (timing === 'now') {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'send_now';
        input.value = '1';
        this.appendChild(input);

        if (!confirm('Send this campaign to all eligible contacts now?')) {
            e.preventDefault();
        }
    }
});

updatePreview();
updateTimingCards();
</script>
</x-app-layout>
