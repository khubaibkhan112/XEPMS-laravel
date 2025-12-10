<?php

namespace App\Console\Commands;

use App\Models\ChannelConnection;
use App\Services\ChannelManager\ChannelClientFactory;
use App\Services\ChannelManager\ReservationImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ImportExpediaReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channel:import-expedia
                            {--connection= : Channel connection ID}
                            {--property= : Property ID to import for}
                            {--start-date= : Start date for import (Y-m-d)}
                            {--end-date= : End date for import (Y-m-d)}
                            {--days=90 : Number of days ahead to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import reservations from Expedia channel connections';

    /**
     * Execute the console command.
     */
    public function handle(ReservationImportService $importService): int
    {
        $connectionId = $this->option('connection');
        $propertyId = $this->option('property');
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $days = (int) $this->option('days');

        // Determine date range
        $start = $startDate ? Carbon::parse($startDate) : Carbon::today();
        $end = $endDate ? Carbon::parse($endDate) : Carbon::today()->addDays($days);

        // Get connections to import from
        $connections = $this->getConnections($connectionId, $propertyId);

        if ($connections->isEmpty()) {
            $this->error('No active Expedia connections found.');
            return Command::FAILURE;
        }

        $this->info(sprintf(
            'Importing reservations from %d connection(s) for period %s to %s',
            $connections->count(),
            $start->toDateString(),
            $end->toDateString()
        ));

        $totalStats = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($connections as $connection) {
            $this->line('');
            $this->info(sprintf(
                'Processing connection: %s (ID: %d, Property: %s)',
                $connection->name,
                $connection->id,
                $connection->property->name ?? 'N/A'
            ));

            $stats = $importService->importReservations($connection, $start, $end);

            $totalStats['success'] += $stats['success'];
            $totalStats['failed'] += $stats['failed'];
            $totalStats['skipped'] += $stats['skipped'];
            $totalStats['errors'] = array_merge($totalStats['errors'], $stats['errors']);

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Created/Updated', $stats['success']],
                    ['Failed', $stats['failed']],
                    ['Skipped', $stats['skipped']],
                ]
            );

            if (!empty($stats['errors'])) {
                $this->warn('Errors encountered:');
                foreach (array_slice($stats['errors'], 0, 10) as $error) {
                    $this->line('  - ' . $error);
                }
                if (count($stats['errors']) > 10) {
                    $this->line(sprintf('  ... and %d more errors', count($stats['errors']) - 10));
                }
            }
        }

        $this->line('');
        $this->info('Import Summary:');
        $this->table(
            ['Metric', 'Total'],
            [
                ['Total Created/Updated', $totalStats['success']],
                ['Total Failed', $totalStats['failed']],
                ['Total Skipped', $totalStats['skipped']],
            ]
        );

        if ($totalStats['failed'] > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Get channel connections to import from.
     *
     * @param string|null $connectionId
     * @param string|null $propertyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getConnections(?string $connectionId, ?string $propertyId)
    {
        $query = ChannelConnection::where('channel', 'expedia')
            ->where('is_active', true)
            ->with('property');

        if ($connectionId) {
            $query->where('id', $connectionId);
        }

        if ($propertyId) {
            $query->where('property_id', $propertyId);
        }

        return $query->get();
    }
}



