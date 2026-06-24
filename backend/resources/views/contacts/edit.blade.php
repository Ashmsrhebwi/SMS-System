<x-app-layout>
@section('page-title', 'Edit Contact')
@section('page-subtitle', 'Update contact information')

<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-4 mb-7">
        <a href="{{ route('contacts.index') }}"
           class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Contact</h2>
            <p class="text-sm text-slate-500 mt-0.5">{{ $contact->name }}</p>
        </div>
    </div>

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

    <form method="POST" action="{{ route('contacts.update', $contact) }}">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-5">

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="name">Full Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $contact->name) }}" required
                       class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="phone">Phone Number <span class="text-red-500">*</span></label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $contact->phone) }}" required
                       class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm font-mono text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="email">Email Address <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $contact->email) }}"
                       class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                       placeholder="jane@example.com">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5" for="notes">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                <textarea id="notes" name="notes" rows="3"
                          class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow resize-none">{{ old('notes', $contact->notes) }}</textarea>
            </div>

            @if($tags->count())
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Tags</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                        @php $checked = in_array($tag->id, old('tags', $contact->tags->pluck('id')->toArray())); @endphp
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                   {{ $checked ? 'checked' : '' }}
                                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-700 bg-slate-100 rounded-full px-2.5 py-1">
                                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $tag->color }}"></span>
                                {{ $tag->name }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="flex items-center justify-between pt-1">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="opted_in" value="1"
                           {{ old('opted_in', $contact->opted_in) ? 'checked' : '' }}
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-medium text-slate-700">Contact has opted in to receive SMS</span>
                </label>
            </div>

        </div>

        <div class="flex items-center gap-3 mt-5">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save Changes
            </button>
            <a href="{{ route('contacts.index') }}"
               class="px-6 py-2.5 rounded-xl text-sm font-semibold text-slate-600 border border-slate-200 bg-white hover:bg-slate-50 transition-colors shadow-sm">
                Cancel
            </a>
        </div>
    </form>

</div>
</x-app-layout>
