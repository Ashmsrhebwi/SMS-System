<x-app-layout>
@section('page-title', 'Country Analytics')
@section('page-subtitle', 'Contact and message distribution by country')

<div class="max-w-7xl mx-auto">

    <h2 class="text-2xl font-bold text-slate-900 tracking-tight mb-7">Country Analytics</h2>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-7">

        <!-- Top Countries Chart -->
        <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="text-base font-semibold text-slate-900 mb-4">Top Countries</h3>
            <canvas id="countryChart" height="250"></canvas>
        </div>

        <!-- Stats Table -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="text-base font-semibold text-slate-900">All Countries</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/70">
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Country</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Contacts</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Sent</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Delivered</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Failed</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($stats as $row)
                        <tr class="hover:bg-slate-50/50 transition-colors even:bg-slate-50/20">
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-slate-900">{{ $row['country'] }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm text-slate-700">{{ number_format($row['contacts']) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm text-slate-700">{{ number_format($row['messages_sent']) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm text-emerald-700 font-medium">{{ number_format($row['delivered']) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm text-red-600">{{ number_format($row['failed']) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-16 bg-slate-100 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full {{ $row['delivery_rate'] >= 80 ? 'bg-emerald-500' : ($row['delivery_rate'] >= 50 ? 'bg-amber-400' : 'bg-slate-300') }}"
                                             style="width: {{ $row['delivery_rate'] }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-600">{{ $row['delivery_rate'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <p class="text-sm text-slate-500">No contacts yet.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('countryChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: @json($topCountries->pluck('country')),
        datasets: [{
            data: @json($topCountries->pluck('contacts')),
            backgroundColor: [
                '#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b',
                '#ef4444','#3b82f6','#ec4899','#14b8a6','#64748b',
            ],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } }
        },
        cutout: '60%',
    }
});
</script>
</x-app-layout>
