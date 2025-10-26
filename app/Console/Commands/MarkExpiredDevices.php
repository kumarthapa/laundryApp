<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarkExpiredDevices extends Command
{
    protected $signature = 'devices:mark-expired';

    protected $description = 'Mark device registrations as EXPIRE when end_date is past due';

    public function handle()
    {
        $today = Carbon::now()->startOfDay()->toDateString();

        // Fetch devices to expire
        $devicesToExpire = DB::table('device_registrations')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today)
            ->where('status', '!=', 'EXPIRE')
            ->get(['device_registration_id', 'device_id', 'license_key', 'status', 'end_date']);

        if ($devicesToExpire->isEmpty()) {
            $this->info('No devices to mark as EXPIRE today.');
            Log::info('MarkExpiredDevices: No devices to mark as EXPIRE on '.$today);

            return 0;
        }

        // Update statuses
        $updated = DB::table('device_registrations')
            ->whereIn('device_registration_id', $devicesToExpire->pluck('device_registration_id'))
            ->update([
                'status' => 'EXPIRE',
                'updated_at' => Carbon::now(),
            ]);

        $this->info("Marked {$updated} device(s) as EXPIRE.");

        // Log details
        foreach ($devicesToExpire as $device) {
            Log::info("Device marked as EXPIRE: ID={$device->device_registration_id}, DeviceID={$device->device_id}, License={$device->license_key}, EndDate={$device->end_date}");
        }

        return 0;
    }
}
