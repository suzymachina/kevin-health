<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HealthController extends Controller
{
    public function ingest(Request $request)
    {
        $apiKey = $request->header('X-API-Key');
        if ($apiKey !== config('services.health.api_key')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->input('data', []);
        $count = 0;

        // Process metrics
        $metrics = $data['metrics'] ?? [];
        foreach ($metrics as $metric) {
            $metricName = $metric['name'] ?? null;
            $units = $metric['units'] ?? null;

            if (!$metricName) continue;

            // Sleep analysis has special structure
            if ($metricName === 'sleep_analysis') {
                foreach ($metric['data'] ?? [] as $entry) {
                    $date = $entry['inBedStart'] ?? $entry['sleepStart'] ?? null;
                    if (!$date) continue;

                    $source = $entry['source'] ?? 'unknown';
                    $qty = $entry['totalSleep'] ?? 0;

                    $inserted = DB::table('health_metrics')->insertOrIgnore([
                        'metric_name' => $metricName,
                        'date' => $date,
                        'qty' => $qty,
                        'units' => $units ?? 'hr',
                        'source' => $source,
                        'raw_json' => json_encode($entry),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $count += $inserted;
                }
            } else {
                foreach ($metric['data'] ?? [] as $entry) {
                    $date = $entry['date'] ?? null;
                    if (!$date) continue;

                    $source = $entry['source'] ?? 'unknown';
                    $qty = $entry['qty'] ?? 0;

                    $inserted = DB::table('health_metrics')->insertOrIgnore([
                        'metric_name' => $metricName,
                        'date' => $date,
                        'qty' => (float) $qty,
                        'units' => $units,
                        'source' => $source,
                        'raw_json' => json_encode($entry),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $count += $inserted;
                }
            }
        }

        // Process workouts
        $workouts = $data['workouts'] ?? [];
        foreach ($workouts as $workout) {
            $name = $workout['name'] ?? null;
            $start = $workout['start'] ?? null;
            if (!$name || !$start) continue;

            $inserted = DB::table('health_workouts')->insertOrIgnore([
                'name' => $name,
                'start' => $start,
                'end' => $workout['end'] ?? null,
                'duration' => $workout['duration'] ?? null,
                'calories' => $workout['activeEnergy'] ?? null,
                'distance' => $workout['distance'] ?? null,
                'raw_json' => json_encode($workout),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $count += $inserted;
        }

        return response()->json(['ok' => true, 'count' => $count]);
    }

    public function summary()
    {
        $today = Carbon::today()->toDateString();

        $steps = DB::table('health_metrics')
            ->where('metric_name', 'step_count')
            ->whereDate('date', $today)
            ->sum('qty');

        $lastSleep = DB::table('health_metrics')
            ->where('metric_name', 'sleep_analysis')
            ->orderByDesc('date')
            ->first();

        $lastWorkout = DB::table('health_workouts')
            ->orderByDesc('start')
            ->first();

        return response()->json([
            'steps_today' => $steps,
            'last_sleep' => $lastSleep ? json_decode($lastSleep->raw_json, true) : null,
            'last_workout' => $lastWorkout ? json_decode($lastWorkout->raw_json, true) : null,
        ]);
    }

    public function sleep(Request $request)
    {
        $days = (int) $request->query('days', 7);

        $sleep = DB::table('health_metrics')
            ->where('metric_name', 'sleep_analysis')
            ->where('date', '>=', Carbon::now()->subDays($days))
            ->orderByDesc('date')
            ->get()
            ->map(fn ($row) => json_decode($row->raw_json, true));

        return response()->json($sleep);
    }

    public function metric(Request $request, string $name)
    {
        $days = (int) $request->query('days', 7);

        $data = DB::table('health_metrics')
            ->where('metric_name', $name)
            ->where('date', '>=', Carbon::now()->subDays($days))
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }

    public function workouts(Request $request)
    {
        $days = (int) $request->query('days', 30);

        $data = DB::table('health_workouts')
            ->where('start', '>=', Carbon::now()->subDays($days))
            ->orderByDesc('start')
            ->get();

        return response()->json($data);
    }

    public function metricsList()
    {
        $metrics = DB::table('health_metrics')
            ->select('metric_name', DB::raw('count(*) as count'), 'units')
            ->groupBy('metric_name', 'units')
            ->orderBy('metric_name')
            ->get();

        return response()->json($metrics);
    }

    public function dashboard()
    {
        $today = Carbon::today()->toDateString();

        $steps = DB::table('health_metrics')
            ->where('metric_name', 'step_count')
            ->whereDate('date', $today)
            ->sum('qty');

        $calories = DB::table('health_metrics')
            ->where('metric_name', 'active_energy')
            ->whereDate('date', $today)
            ->sum('qty');

        $exerciseMinutes = DB::table('health_metrics')
            ->where('metric_name', 'apple_exercise_time')
            ->whereDate('date', $today)
            ->sum('qty');

        $restingHR = DB::table('health_metrics')
            ->where('metric_name', 'resting_heart_rate')
            ->orderByDesc('date')
            ->value('qty');

        // Sleep data (last 7 days)
        $sleepData = DB::table('health_metrics')
            ->where('metric_name', 'sleep_analysis')
            ->where('date', '>=', Carbon::now()->subDays(7))
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                $raw = json_decode($row->raw_json, true);
                return [
                    'date' => Carbon::parse($row->date)->format('M j'),
                    'total' => round(($raw['totalSleep'] ?? $row->qty) / 60, 1),
                    'deep' => round(($raw['deep'] ?? 0) / 60, 1),
                    'rem' => round(($raw['rem'] ?? 0) / 60, 1),
                    'core' => round(($raw['core'] ?? 0) / 60, 1),
                ];
            });

        // Heart rate (last 24h)
        $heartRate = DB::table('health_metrics')
            ->where('metric_name', 'heart_rate')
            ->where('date', '>=', Carbon::now()->subDay())
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => Carbon::parse($row->date)->format('H:i'),
                'qty' => $row->qty,
            ]);

        // Recent workouts
        $workouts = DB::table('health_workouts')
            ->orderByDesc('start')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'steps', 'calories', 'exerciseMinutes', 'restingHR',
            'sleepData', 'heartRate', 'workouts'
        ));
    }
}
