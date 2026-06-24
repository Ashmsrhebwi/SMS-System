<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'FeRa Clinic SMS') }}@hasSection('title') — @yield('title')@endif</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-800">

<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 bg-indigo-950 flex flex-col select-none">

        <!-- Brand -->
        <div class="px-5 py-5 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-indigo-500/20 rounded-xl flex items-center justify-center flex-shrink-0 ring-1 ring-indigo-400/20">
                    <svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-white font-bold text-sm leading-tight tracking-tight">FeRa Clinic</p>
                    <p class="text-indigo-400/70 text-xs font-medium">SMS Platform</p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">

            <p class="px-3 mb-2 text-[10px] font-semibold text-indigo-500/80 uppercase tracking-widest">Navigation</p>

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('dashboard') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <!-- Campaigns -->
            <a href="{{ route('campaigns.index') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('campaigns.*') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('campaigns.*') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                <span>Campaigns</span>
            </a>

            <!-- Contacts -->
            <a href="{{ route('contacts.index') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('contacts.*') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('contacts.*') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Contacts</span>
            </a>

            @if(Auth::user()->isAdmin())
            <!-- Admin-only section -->
            <p class="px-3 mt-4 mb-2 text-[10px] font-semibold text-indigo-500/80 uppercase tracking-widest">Admin</p>

            <!-- Tags -->
            <a href="{{ route('tags.index') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('tags.*') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('tags.*') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span>Tags</span>
            </a>

            <!-- Segments -->
            <a href="{{ route('segments.index') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('segments.*') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('segments.*') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
                <span>Segments</span>
            </a>

            <!-- Templates -->
            <a href="{{ route('templates.index') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('templates.*') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('templates.*') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Templates</span>
            </a>

            <!-- Blacklist -->
            <a href="{{ route('blacklist.index') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('blacklist.*') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('blacklist.*') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                <span>Blacklist</span>
            </a>

            <!-- Users -->
            <a href="{{ route('users.index') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('users.*') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('users.*') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <span>Users</span>
            </a>

            <!-- Audit Log -->
            <a href="{{ route('audit.index') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('audit.*') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('audit.*') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Audit Log</span>
            </a>

            <p class="px-3 mt-4 mb-2 text-[10px] font-semibold text-indigo-500/80 uppercase tracking-widest">Reports</p>

            <!-- Cost Report -->
            <a href="{{ route('reports.costs') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('reports.costs') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('reports.costs') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1c-1.11 0-2.08.402-2.599 1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Cost Report</span>
            </a>

            <!-- Country Analytics -->
            <a href="{{ route('reports.countries') }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('reports.countries') ? 'bg-indigo-600/40 text-white shadow-sm ring-1 ring-white/10' : 'text-indigo-300/80 hover:text-white hover:bg-white/5' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ request()->routeIs('reports.countries') ? 'text-indigo-300' : 'text-indigo-400/60 group-hover:text-indigo-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Countries</span>
            </a>
            @endif

        </nav>

        <!-- User / Logout -->
        <div class="px-3 py-4 border-t border-white/5">
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/5 mb-1">
                <div class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center flex-shrink-0 ring-2 ring-indigo-400/30">
                    <span class="text-white text-xs font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-xs font-semibold truncate leading-tight">{{ Auth::user()->name }}</p>
                    <p class="text-indigo-400/60 text-xs truncate">{{ Auth::user()->isAdmin() ? 'Administrator' : 'Standard User' }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs text-indigo-400/70 hover:text-white hover:bg-white/5 w-full text-left transition-all duration-150 mt-0.5">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>

    </aside>

    <!-- Main area -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Top bar -->
        <header class="flex-shrink-0 bg-white border-b border-slate-200/80 px-8 py-4 flex items-center justify-between shadow-sm gap-4">
            <div class="flex-shrink-0 min-w-0">
                @hasSection('page-title')
                    <h1 class="text-base font-semibold text-slate-900 leading-tight">@yield('page-title')</h1>
                @else
                    <h1 class="text-base font-semibold text-slate-900 leading-tight">{{ config('app.name', 'FeRa Clinic SMS') }}</h1>
                @endif
                @hasSection('page-subtitle')
                    <p class="text-xs text-slate-400 mt-0.5">@yield('page-subtitle')</p>
                @endif
            </div>

            <!-- Global Search -->
            <div class="flex-1 max-w-sm" x-data="globalSearch" x-on:click.away="close()">
                <div class="relative">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(results.length) open=true" @keydown.escape="close()"
                           class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-slate-50"
                           placeholder="Search contacts, campaigns…">
                    <div x-show="open && results.length > 0" x-cloak
                         class="absolute top-full left-0 right-0 mt-1.5 bg-white rounded-xl shadow-xl border border-slate-200 z-50 max-h-80 overflow-y-auto">
                        <template x-for="group in results" :key="group.group">
                            <div>
                                <p class="px-4 pt-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider" x-text="group.group"></p>
                                <template x-for="item in group.items" :key="item.id">
                                    <a :href="item.url"
                                       class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 flex-shrink-0" :style="item.color ? `background-color: ${item.color}` : ''"></span>
                                        <span>
                                            <span class="text-sm font-medium text-slate-800" x-text="item.label"></span>
                                            <span class="text-xs text-slate-400 ml-1.5" x-text="item.sub"></span>
                                        </span>
                                    </a>
                                </template>
                            </div>
                        </template>
                    </div>
                    <div x-show="open && query.length > 1 && results.length === 0 && !loading" x-cloak
                         class="absolute top-full left-0 right-0 mt-1.5 bg-white rounded-xl shadow-xl border border-slate-200 z-50 px-4 py-4 text-sm text-slate-400 text-center">
                        No results found
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 flex-shrink-0">
                @if(Auth::user()->isStandard())
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                        Standard User
                    </span>
                @endif
                <div class="flex items-center gap-2.5 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5">
                    <div class="w-7 h-7 bg-indigo-100 rounded-full flex items-center justify-center">
                        <span class="text-indigo-700 text-xs font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                    </div>
                    <span class="text-sm text-slate-700 font-medium hidden sm:block">{{ Auth::user()->name }}</span>
                </div>
            </div>
        </header>

        <!-- Page content -->
        <main class="flex-1 overflow-y-auto px-8 py-7">
            {{ $slot }}
        </main>

    </div>

</div>

</body>
</html>
