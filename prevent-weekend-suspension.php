<?php
// File: includes/hooks/prevent_weekend_suspension.php

use WHMCS\Database\Capsule;

// Hook to the daily cron job
add_hook('DailyCronJob', 1, function($vars) {
    // Get today's day of the week
    $today = date('N'); // 1 (for Monday) through 7 (for Sunday)
    
    // Check if today is Saturday (6) or Sunday (7)
    if ($today == 6 || $today == 7) {
        // Log that suspensions are being delayed
        logActivity("Suspension delayed due to weekend. Services will be suspended on Monday.");

        // Skip suspension logic
        return;
    }

    // Check if today is Monday (1)
    if ($today == 1) {
        // Find services that should have been suspended over the weekend
        $services = Capsule::table('tblhosting')
            ->where('domainstatus', 'Active')
            ->where(function ($query) {
                $query->whereDate('nextduedate', '<', date('Y-m-d'))
                      ->whereDate('suspendreason', '!=', 'Weekend Suspension');
            })
            ->get();

        foreach ($services as $service) {
            // Suspend the service
            Capsule::table('tblhosting')
                ->where('id', $service->id)
                ->update([
                    'domainstatus' => 'Suspended',
                    'suspendreason' => 'Weekend Suspension',
                    'suspenddate' => date('Y-m-d')
                ]);

            // Add an entry to the activity log
            logActivity("Service ID {$service->id} suspended on Monday due to weekend delay.");
        }
    }
});
