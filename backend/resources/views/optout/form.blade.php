<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribe — FeRa Clinic</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 max-w-md w-full p-8">
    <div class="text-center mb-6">
        <p class="text-lg font-bold text-gray-900">FeRa Clinic</p>
        <h1 class="text-xl font-semibold text-gray-800 mt-2">Unsubscribe from SMS</h1>
        <p class="text-sm text-gray-500 mt-2">You will no longer receive SMS messages from FeRa Clinic.</p>
    </div>

    <form method="POST" action="{{ route('optout.process') }}">
        @csrf
        @if($contact)
            <input type="hidden" name="phone" value="{{ $contact->phone }}">
            <div class="bg-gray-50 rounded-lg p-3 mb-4 text-sm text-gray-600">
                Unsubscribing: <span class="font-medium">{{ $contact->name }}</span> ({{ $contact->phone }})
            </div>
        @else
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Your phone number</label>
                <input type="text" name="phone" placeholder="+44 7XXX XXXXXX"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        @endif

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
            <textarea name="reason" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Optional: let us know why..."></textarea>
        </div>

        <button type="submit" class="bg-red-600 text-white w-full px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition">
            Unsubscribe Me
        </button>
    </form>
</div>
</body>
</html>
