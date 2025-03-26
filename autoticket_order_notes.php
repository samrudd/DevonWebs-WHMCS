<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// Hook to check for order notes and create a ticket if notes exist
add_hook('AfterShoppingCartCheckout', 1, function($vars) {
    $orderId = $vars['OrderID'];

    // Get order details
    $order = Capsule::table('tblorders')->where('id', $orderId)->first();
    if (!$order || empty($order->notes)) {
        return; // Exit if no order notes
    }

    $userId = $order->userid;

    // Get user details
    $client = Capsule::table('tblclients')->where('id', $userId)->first();
    if (!$client) {
        return;
    }

    // Ticket parameters
    $deptId = 1; // Change this to your support department ID
    $subject = "New Order with Admin Notes - Order #{$orderId}";
    $message = "A new order has been placed with additional notes from the admin.\n\n"
        . "**Client Details:**\n"
        . "Name: {$client->firstname} {$client->lastname}\n"
        . "Email: {$client->email}\n"
        . "Client ID: {$client->id}\n\n"
        . "**Order Details:**\n"
        . "Order ID: {$orderId}\n"
        . "Invoice ID: {$order->invoiceid}\n"
        . "Amount: {$order->amount} {$order->currency}\n"
        . "Payment Method: {$order->paymentmethod}\n\n"
        . "**Admin Notes:**\n"
        . "{$order->notes}\n\n"
        . "Please review and take necessary actions.";

    // Open the ticket
    $adminUsername = ''; // Leave blank unless needed for API authentication
    $command = 'OpenTicket';
    $postData = [
        'deptid' => $deptId,
        'subject' => $subject,
        'message' => $message,
        'priority' => 'Medium',
        'clientid' => $userId,
    ];

    $results = localAPI($command, $postData, $adminUsername);

    if ($results['result'] === 'success') {
        logActivity("Auto Ticket Created for Order with Notes - Order ID: {$orderId}");
    } else {
        logActivity("Failed to Create Auto Ticket for Order with Notes - Order ID: {$orderId}. Error: " . $results['message']);
    }
});

?>
