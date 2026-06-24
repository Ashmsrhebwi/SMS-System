<x-app-layout>
@section('page-title', 'New Segment')
@section('page-subtitle', 'Build a dynamic contact segment')

<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-4 mb-7">
        <a href="{{ route('segments.index') }}"
           class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">New Segment</h2>
    </div>

    @if($errors->any())
        <div class="mb-5 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('segments.store') }}" x-data="segmentBuilder()">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-5">

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">Segment Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       placeholder="e.g. VIP Patients, Implant Leads">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">Description <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="text" name="description" value="{{ old('description') }}"
                       class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       placeholder="Brief description of who this segment targets">
            </div>

            <div>
                <div class="flex items-center justify-between mb-3">
                    <label class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Conditions</label>
                    <button type="button" @click="addCondition()"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Condition
                    </button>
                </div>

                <p class="text-xs text-slate-400 mb-3">Leave empty to match all opted-in contacts.</p>

                <template x-if="conditions.length === 0">
                    <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center">
                        <p class="text-sm text-slate-400">No conditions — segment will include all opted-in contacts.</p>
                    </div>
                </template>

                <div class="space-y-3">
                    <template x-for="(cond, index) in conditions" :key="index">
                        <div class="flex items-start gap-3 bg-slate-50 rounded-xl p-4">
                            <div class="flex-1 grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Field</label>
                                    <select :name="'conditions[' + index + '][field]'" x-model="cond.field"
                                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                                        <option value="tag">Tag contains</option>
                                        <option value="opted_in">Opt-in status</option>
                                        <option value="phone_country">Phone country code</option>
                                        <option value="created_after">Created after</option>
                                        <option value="created_before">Created before</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Value</label>
                                    <template x-if="cond.field === 'tag'">
                                        <select :name="'conditions[' + index + '][value]'" x-model="cond.value"
                                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                                            <option value="">Select tag…</option>
                                            @foreach($tags as $tag)
                                                <option value="{{ $tag->name }}">{{ $tag->name }}</option>
                                            @endforeach
                                        </select>
                                    </template>
                                    <template x-if="cond.field === 'opted_in'">
                                        <select :name="'conditions[' + index + '][value]'" x-model="cond.value"
                                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                                            <option value="1">Opted In</option>
                                            <option value="0">Opted Out</option>
                                        </select>
                                    </template>
                                    <template x-if="cond.field !== 'tag' && cond.field !== 'opted_in'">
                                        <input :type="(cond.field === 'created_after' || cond.field === 'created_before') ? 'date' : 'text'"
                                               :name="'conditions[' + index + '][value]'"
                                               x-model="cond.value"
                                               :placeholder="cond.field === 'phone_country' ? '+44' : 'YYYY-MM-DD'"
                                               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </template>
                                </div>
                            </div>
                            <button type="button" @click="removeCondition(index)"
                                    class="mt-5 w-7 h-7 flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        <div class="flex items-center gap-3 mt-5">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
                Create Segment
            </button>
            <a href="{{ route('segments.index') }}"
               class="px-6 py-2.5 rounded-xl text-sm font-semibold text-slate-600 border border-slate-200 bg-white hover:bg-slate-50 transition-colors shadow-sm">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function segmentBuilder() {
    return {
        conditions: @json(old('conditions', [])),
        addCondition() {
            this.conditions.push({ field: 'tag', value: '' });
        },
        removeCondition(index) {
            this.conditions.splice(index, 1);
        }
    };
}
</script>
</x-app-layout>
