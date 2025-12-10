<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelConnection;
use App\Services\ChannelManager\ReservationImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ChannelImportController extends Controller
{
    public function __construct(
        protected readonly ReservationImportService $importService
    ) {
    }

    /**
     * Import reservations from a channel connection.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'connection_id' => ['required', 'exists:channel_connections,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $connection = ChannelConnection::findOrFail($validated['connection_id']);

        if (!$connection->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Channel connection is not active.',
            ], 400);
        }

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : Carbon::today();

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : Carbon::today()->addDays($validated['days'] ?? 90);

        try {
            $stats = $this->importService->importReservations(
                $connection,
                $startDate,
                $endDate
            );

            return response()->json([
                'success' => true,
                'message' => 'Import completed.',
                'data' => [
                    'connection_id' => $connection->id,
                    'connection_name' => $connection->name,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'stats' => $stats,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import reservations for all active connections of a specific channel.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function importByChannel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['required', Rule::in(ChannelConnection::supportedChannels())],
            'property_id' => ['nullable', 'exists:properties,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $query = ChannelConnection::where('channel', $validated['channel'])
            ->where('is_active', true);

        if (isset($validated['property_id'])) {
            $query->where('property_id', $validated['property_id']);
        }

        $connections = $query->get();

        if ($connections->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active connections found for the specified channel.',
            ], 404);
        }

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : Carbon::today();

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : Carbon::today()->addDays($validated['days'] ?? 90);

        $results = [];
        $totalStats = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($connections as $connection) {
            try {
                $stats = $this->importService->importReservations(
                    $connection,
                    $startDate,
                    $endDate
                );

                $results[] = [
                    'connection_id' => $connection->id,
                    'connection_name' => $connection->name,
                    'stats' => $stats,
                ];

                $totalStats['success'] += $stats['success'];
                $totalStats['failed'] += $stats['failed'];
                $totalStats['skipped'] += $stats['skipped'];
                $totalStats['errors'] = array_merge($totalStats['errors'], $stats['errors']);
            } catch (\Exception $e) {
                $results[] = [
                    'connection_id' => $connection->id,
                    'connection_name' => $connection->name,
                    'error' => $e->getMessage(),
                ];

                $totalStats['failed']++;
                $totalStats['errors'][] = sprintf(
                    'Connection %s: %s',
                    $connection->name,
                    $e->getMessage()
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Import completed for all connections.',
            'data' => [
                'channel' => $validated['channel'],
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'connections_processed' => $connections->count(),
                'results' => $results,
                'total_stats' => $totalStats,
            ],
        ]);
    }
}





