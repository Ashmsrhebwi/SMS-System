<x-app-layout>
@section('page-title', 'Import Contacts')
@section('page-subtitle', 'Upload a spreadsheet to add contacts in bulk')

<div class="max-w-3xl mx-auto">

    <!-- Page Header -->
    <div class="flex items-center gap-4 mb-7">
        <a href="{{ route('contacts.index') }}"
           class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Import Contacts</h2>
            <p class="text-sm text-slate-500 mt-0.5">Upload an Excel or CSV file to add contacts in bulk.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 flex gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-4 rounded-xl text-sm">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
        </div>
    @endif

    <form method="POST" action="{{ route('contacts.import.store') }}" enctype="multipart/form-data" id="importForm">
        @csrf

        <!-- Upload Zone -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-5">
            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <span class="w-5 h-5 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </span>
                Select File
            </h3>

            <div id="dropZone"
                 class="border-2 border-dashed border-slate-200 rounded-xl p-10 text-center hover:border-indigo-400 hover:bg-indigo-50/30 transition-all cursor-pointer group"
                 onclick="document.getElementById('fileInput').click()">
                <div class="w-14 h-14 bg-slate-100 group-hover:bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-4 transition-colors">
                    <svg class="w-7 h-7 text-slate-400 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-slate-700 group-hover:text-indigo-700 transition-colors">Click to browse or drag & drop</p>
                <p class="text-xs text-slate-400 mt-1">Supports: XLSX, XLS, CSV — max 10 MB</p>
                <div id="fileNameDisplay" class="hidden mt-3">
                    <span class="inline-flex items-center gap-2 bg-indigo-50 text-indigo-700 text-xs font-semibold px-3 py-1.5 rounded-lg border border-indigo-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span id="fileNameText"></span>
                    </span>
                </div>
                <input type="file" id="fileInput" name="file" accept=".xlsx,.xls,.csv" class="hidden"
                       onchange="handleFileSelect(this)">
            </div>
        </div>

        <!-- Column Guide -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-5">
            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <span class="w-5 h-5 bg-indigo-100 rounded-md flex items-center justify-center text-indigo-600">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M3 6h18M3 18h18"/>
                    </svg>
                </span>
                Required Column Names
            </h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="w-5 h-5 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                    </span>
                    <div>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <code class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-1.5 py-0.5 rounded">name</code>
                            <span class="text-slate-400 text-xs">or</span>
                            <code class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-1.5 py-0.5 rounded">full_name</code>
                        </div>
                        <p class="text-xs text-red-600 font-medium mt-1">Required</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="w-5 h-5 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                    </span>
                    <div>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <code class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-1.5 py-0.5 rounded">phone</code>
                            <span class="text-slate-400 text-xs">or</span>
                            <code class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-1.5 py-0.5 rounded">mobile</code>
                        </div>
                        <p class="text-xs text-red-600 font-medium mt-1">Required</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="w-5 h-5 bg-slate-200 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    </span>
                    <div>
                        <code class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-1.5 py-0.5 rounded">last_visit</code>
                        <p class="text-xs text-slate-400 font-medium mt-1">Optional</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="w-5 h-5 bg-slate-200 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    </span>
                    <div>
                        <code class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-1.5 py-0.5 rounded">notes</code>
                        <p class="text-xs text-slate-400 font-medium mt-1">Optional</p>
                    </div>
                </div>
            </div>

            <!-- Phone format hint -->
            <div class="mt-4 flex items-start gap-2 text-xs text-slate-500 bg-amber-50 border border-amber-100 rounded-xl px-3 py-2.5">
                <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>
                    UK phone formats accepted:
                    <code class="font-mono font-semibold text-amber-700">07XXX XXXXXX</code>,
                    <code class="font-mono font-semibold text-amber-700">+447XXXXXXXXX</code>,
                    <code class="font-mono font-semibold text-amber-700">447XXXXXXXXX</code>
                </span>
            </div>
        </div>

        <!-- Duplicate Handling -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-5">
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Duplicate Contact Handling</h3>
            <div class="grid grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" name="duplicate_action" value="skip" checked class="sr-only peer">
                    <div class="border-2 border-slate-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 rounded-xl p-4 transition-all">
                        <p class="text-sm font-semibold text-slate-800">Skip Duplicates</p>
                        <p class="text-xs text-slate-400 mt-0.5">Skip rows where phone or email already exists</p>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="duplicate_action" value="update" class="sr-only peer">
                    <div class="border-2 border-slate-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 rounded-xl p-4 transition-all">
                        <p class="text-sm font-semibold text-slate-800">Update Existing</p>
                        <p class="text-xs text-slate-400 mt-0.5">Update name, email, and notes for matching contacts</p>
                    </div>
                </label>
            </div>
        </div>

        <button type="submit" id="submitBtn"
                class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 text-white px-6 py-3 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200 disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            Import Contacts
        </button>
    </form>
</div>

<script>
function handleFileSelect(input) {
    if (input.files.length > 0) {
        const name = input.files[0].name;
        document.getElementById('fileNameText').textContent = name;
        document.getElementById('fileNameDisplay').classList.remove('hidden');
    }
}
</script>
</x-app-layout>
