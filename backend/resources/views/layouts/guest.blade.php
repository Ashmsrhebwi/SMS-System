<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'FeRa Clinic SMS') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50">

<div class="min-h-screen flex">

    <!-- Left panel — branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-indigo-950 flex-col justify-between p-12">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-indigo-400/20 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <span class="text-white font-bold text-lg">FeRa Clinic</span>
            </div>
        </div>

        <div>
            <h1 class="text-4xl font-bold text-white leading-tight mb-4">
                SMS Campaigns<br>
                <span class="text-indigo-300">Made Simple</span>
            </h1>
            <p class="text-indigo-400 text-base leading-relaxed max-w-sm">
                Send personalised appointment reminders and promotions to your patients. Track delivery in real-time.
            </p>

            <div class="mt-10 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-indigo-800 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-indigo-200 text-sm">Alphanumeric sender — "FeRa Clinic" shows as sender</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-indigo-800 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <p class="text-indigo-200 text-sm">Real-time delivery tracking and click analytics</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-indigo-800 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
                        </svg>
                    </div>
                    <p class="text-indigo-200 text-sm">Bulk import from Excel — UK GDPR compliant</p>
                </div>
            </div>
        </div>

        <p class="text-indigo-600 text-xs">UK GDPR &amp; PECR Compliant &middot; Powered by Twilio</p>
    </div>

    <!-- Right panel — form -->
    <div class="flex-1 flex items-center justify-center p-8">
        <div class="w-full max-w-sm">
            <div class="lg:hidden flex items-center gap-2 mb-8">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <span class="font-bold text-slate-900">FeRa Clinic SMS</span>
            </div>
            {{ $slot }}
        </div>
    </div>

</div>

</body>
</html>
