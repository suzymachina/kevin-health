<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <meta http-equiv="refresh" content="300">
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Health Dashboard</h1>

        {{-- Today's Summary --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-900 rounded-xl p-5 border border-gray-800">
                <div class="text-gray-400 text-sm mb-1">Steps</div>
                <div class="text-2xl font-bold">{{ number_format($steps) }}</div>
            </div>
            <div class="bg-gray-900 rounded-xl p-5 border border-gray-800">
                <div class="text-gray-400 text-sm mb-1">Active Calories</div>
                <div class="text-2xl font-bold">{{ number_format($calories) }} <span class="text-sm text-gray-500">kcal</span></div>
            </div>
            <div class="bg-gray-900 rounded-xl p-5 border border-gray-800">
                <div class="text-gray-400 text-sm mb-1">Exercise</div>
                <div class="text-2xl font-bold">{{ number_format($exerciseMinutes) }} <span class="text-sm text-gray-500">min</span></div>
            </div>
            <div class="bg-gray-900 rounded-xl p-5 border border-gray-800">
                <div class="text-gray-400 text-sm mb-1">Resting HR</div>
                <div class="text-2xl font-bold">{{ $restingHR ? number_format($restingHR) : '—' }} <span class="text-sm text-gray-500">bpm</span></div>
            </div>
        </div>

        {{-- Charts --}}
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-900 rounded-xl p-5 border border-gray-800">
                <h2 class="text-lg font-semibold mb-4">Sleep (Last 7 Days)</h2>
                <canvas id="sleepChart" height="200"></canvas>
            </div>
            <div class="bg-gray-900 rounded-xl p-5 border border-gray-800">
                <h2 class="text-lg font-semibold mb-4">Heart Rate (Last 24h)</h2>
                <canvas id="hrChart" height="200"></canvas>
            </div>
        </div>

        {{-- Recent Workouts --}}
        <div class="bg-gray-900 rounded-xl p-5 border border-gray-800">
            <h2 class="text-lg font-semibold mb-4">Recent Workouts</h2>
            @if($workouts->isEmpty())
                <p class="text-gray-500">No workouts recorded yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-gray-400 border-b border-gray-800">
                                <th class="text-left py-2 pr-4">Workout</th>
                                <th class="text-left py-2 pr-4">Date</th>
                                <th class="text-right py-2 pr-4">Duration</th>
                                <th class="text-right py-2 pr-4">Calories</th>
                                <th class="text-right py-2">Distance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workouts as $w)
                                <tr class="border-b border-gray-800/50">
                                    <td class="py-2 pr-4 font-medium">{{ str_replace('_', ' ', $w->name) }}</td>
                                    <td class="py-2 pr-4 text-gray-400">{{ \Carbon\Carbon::parse($w->start)->format('M j, g:ia') }}</td>
                                    <td class="py-2 pr-4 text-right">{{ $w->duration ? round($w->duration / 60) . ' min' : '—' }}</td>
                                    <td class="py-2 pr-4 text-right">{{ $w->calories ? number_format($w->calories) : '—' }}</td>
                                    <td class="py-2 text-right">{{ $w->distance ? round($w->distance, 2) . ' km' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <script>
        const chartDefaults = {
            color: '#9ca3af',
            borderColor: '#1f2937',
        };
        Chart.defaults.color = '#9ca3af';
        Chart.defaults.borderColor = '#374151';

        // Sleep Chart
        const sleepData = @json($sleepData);
        if (sleepData.length > 0) {
            new Chart(document.getElementById('sleepChart'), {
                type: 'bar',
                data: {
                    labels: sleepData.map(d => d.date),
                    datasets: [
                        { label: 'Deep', data: sleepData.map(d => d.deep), backgroundColor: '#6366f1' },
                        { label: 'REM', data: sleepData.map(d => d.rem), backgroundColor: '#8b5cf6' },
                        { label: 'Core', data: sleepData.map(d => d.core), backgroundColor: '#a78bfa' },
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true, title: { display: true, text: 'Hours' } }
                    },
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        // Heart Rate Chart
        const hrData = @json($heartRate);
        if (hrData.length > 0) {
            new Chart(document.getElementById('hrChart'), {
                type: 'line',
                data: {
                    labels: hrData.map(d => d.date),
                    datasets: [{
                        label: 'BPM',
                        data: hrData.map(d => d.qty),
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239,68,68,0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 0,
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { title: { display: true, text: 'BPM' } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
    </script>
</body>
</html>
