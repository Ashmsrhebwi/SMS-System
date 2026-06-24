<x-app-layout>
@section('page-title', 'Template Categories')
@section('page-subtitle', 'Organise SMS templates into categories')

<div class="max-w-3xl mx-auto">

    <div class="flex items-center justify-between mb-7">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Template Categories</h2>
            <p class="text-sm text-slate-500 mt-0.5">Group templates like Offers, Appointments, Reminders.</p>
        </div>
        <a href="{{ route('templates.index') }}"
           class="inline-flex items-center gap-2 border border-slate-200 bg-white text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-colors shadow-sm">
            ← Templates
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

    <!-- Add new category -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-5">
        <h3 class="text-sm font-semibold text-slate-700 mb-3">Add Category</h3>
        <form method="POST" action="{{ route('template-categories.store') }}" class="flex items-center gap-3">
            @csrf
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                   class="flex-1 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                   placeholder="Category name e.g. Offers, Appointments">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm">
                Add
            </button>
        </form>
        @error('name')
        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
        @enderror
    </div>

    <!-- Existing categories -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        @forelse($categories as $category)
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-50 last:border-0 hover:bg-slate-50/60 transition-colors" x-data="{ editing: false }">
            <div class="flex-1">
                <div x-show="!editing">
                    <p class="text-sm font-semibold text-slate-900">{{ $category->name }}</p>
                    <p class="text-xs text-slate-400">{{ $category->templates_count }} template{{ $category->templates_count !== 1 ? 's' : '' }}</p>
                </div>
                <div x-show="editing" x-cloak>
                    <form method="POST" action="{{ route('template-categories.update', $category) }}" class="flex items-center gap-2">
                        @csrf @method('PUT')
                        <input type="text" name="name" value="{{ $category->name }}" required maxlength="100"
                               class="border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-52">
                        <button type="submit" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors">Save</button>
                        <button type="button" @click="editing = false" class="text-xs text-slate-500 px-2 py-1.5">Cancel</button>
                    </form>
                </div>
            </div>
            <div class="flex items-center gap-2" x-show="!editing">
                <button @click="editing = true"
                        class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg transition-colors">
                    Edit
                </button>
                <form method="POST" action="{{ route('template-categories.destroy', $category) }}" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit"
                            onclick="return confirm('Delete category \'{{ addslashes($category->name) }}\'?')"
                            class="text-xs font-semibold text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 px-2.5 py-1.5 rounded-lg transition-colors">
                        Delete
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <p class="text-sm text-slate-500">No categories yet. Add one above.</p>
        </div>
        @endforelse
    </div>

</div>
</x-app-layout>
