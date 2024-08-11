<?php

use WHMCS\Database\Capsule;
use WHMCS\View\Menu\Item as MenuItem;

// Hook to add "Pause/Unpause" button in the sidebar under the Actions section
add_hook('ClientAreaPrimarySidebar', 1, function($primarySidebar) {
    $service = Menu::context('service');

    if ($service) {
        // Determine the appropriate label and action based on the service status
        $label = $service->status == 'Active' ? 'Pause Service' : 'Unpause Service';
        $action = $service->status == 'Active' ? 'suspend' : 'unsuspend';

        // Add the button to the "Actions" section of the sidebar
        if (!is_null($primarySidebar->getChild('Service Details Actions'))) {
            $primarySidebar->getChild('Service Details Actions')
                ->addChild('suspendControl', [
                    'label' => $label,
                    'uri' => 'clientarea.php?action=productdetails&id=' . $service->id . '&modop=' . $action,
                    'icon' => 'fa-pause', // Use an appropriate icon here
                    'order' => 20, // Position in the list
                ]);
        }
    }
});

// Hook to handle the suspension/unsuspension action
add_hook('ClientAreaPageProductDetails', 1, function($vars) {
    if (isset($_GET['modop']) && $_GET['modop'] == 'suspend') {
        $result = suspendProduct($vars['serviceid']);
        if ($result['status'] == 'success') {
            redir('action=productdetails&id=' . $vars['serviceid']);
        } else {
            echo '<div class="alert alert-danger">' . $result['message'] . '</div>';
        }
    } elseif (isset($_GET['modop']) && $_GET['modop'] == 'unsuspend') {
        $result = unsuspendProduct($vars['serviceid']);
        if ($result['status'] == 'success') {
            redir('action=productdetails&id=' . $vars['serviceid']);
        } else {
            echo '<div class="alert alert-danger">' . $result['message'] . '</div>';
        }
    }
});

// Suspend a product
function suspendProduct($serviceId) {
    // Get the service
    $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();

    // Check if the service is eligible for suspension
    if ($service->domainstatus != 'Active') {
        return ['status' => 'error', 'message' => 'Service is not eligible for suspension.'];
    }

    // Suspend the service
    $result = localAPI('ModuleSuspend', ['accountid' => $serviceId, 'suspendreason' => 'Suspended by customer request']);

    if ($result['result'] == 'success') {
        Capsule::table('tblhosting')->where('id', $serviceId)->update(['domainstatus' => 'Suspended']);
        return ['status' => 'success', 'message' => 'Service suspended successfully.'];
    }

    return ['status' => 'error', 'message' => 'Failed to suspend service.'];
}

// Unsuspend a product
function unsuspendProduct($serviceId) {
    // Get the service
    $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();

    // Check if the service is eligible for unsuspension
    if ($service->domainstatus != 'Suspended') {
        return ['status' => 'error', 'message' => 'Service is not eligible for unsuspension.'];
    }

    // Unsuspend the service
    $result = localAPI('ModuleUnsuspend', ['accountid' => $serviceId]);

    if ($result['result'] == 'success') {
        Capsule::table('tblhosting')->where('id', $serviceId)->update(['domainstatus' => 'Active']);
        return ['status' => 'success', 'message' => 'Service unsuspended successfully.'];
    }

    return ['status' => 'error', 'message' => 'Failed to unsuspend service.'];
}
