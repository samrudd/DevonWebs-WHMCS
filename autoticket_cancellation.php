<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

add_hook('CancellationRequest', 1, function($vars) {
    $userId = $vars['userid'];
    $serviceId = $vars['relid'];
    $reason = $vars['reason'];
    $type = $vars['type']; // Immediate or End of Billing Period

    // Get user details
    $client = Capsule::table('tblclients')
        ->where('id', $userId)
        ->first();

    // Get service details
    $service = Capsule::table('tblhosting')
        ->where('id', $serviceId)
        ->first();

    if (!$client || !$service) {
        return;
    }

    // Ticket parameters
    $deptId = 1; // Change this to your support department ID
    $subject = "Cancellation Request for {$service->domain}";
    $message = "Client {$client->firstname} {$client->lastname} ({$client->email}) has requested a cancellation.\n\n"
        . "Service: {$service->domain} ({$service->packageid})\n"
        . "Type: {$type}\n"
        . "Reason: {$reason}\n\n"
        . "Please review and take necessary action.";

    // Open the ticket
    $adminUsername = ''; // Leave blank unless needed for API authentication
    $command = 'OpenTicket';
    $postData = [
        'deptid' => $deptId,
        'subject' => $subject,
        'message' => $message,
        'priority' => 'Medium',
        'clientid' => $userId,
        'serviceid' => $serviceId,
    ];

    $results = localAPI($command, $postData, $adminUsername);

    if ($results['result'] === 'success') {
        logActivity("Auto Ticket Created for Cancellation Request - Service ID: {$serviceId}");
    } else {
        logActivity("Failed to Create Auto Ticket for Cancellation Request - Service ID: {$serviceId}. Error: " . $results['message']);
    }
});

?>