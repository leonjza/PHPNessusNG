<?php

include __DIR__ . '/../vendor/autoload.php';

// Prepare the connection to the API
$nessus = new Nessus\Client('username', 'password', '192.168.56.101');

// Configure a proxy to use
$nessus->configureProxy('127.0.0.1', 8081)->useProxy();

// Get the Server properties
// GET /scans
$scans = $nessus->scans()->via('get');
echo '[+] Scans Timestamp: ' . $scans->timestamp . PHP_EOL;

// Loop over the scans printing some information
$scan_id = null;
if (null !== $scans->scans) {
    foreach ($scans->scans as $scan) {
        print '[+] Scan ' . $scan->id . ': (' . $scan->name . ') status: ' . $scan->status . PHP_EOL;
        if ('completed' == $scan->status) {
            $scan_id = $scan->id;
        }
    }
}

// Prepare a scan for download. To do this we need to first
// schedule a export job. Once this is done, we can download the
// report in the requested format.

if (null !== $scan_id) {
    // Lets take the first scan from the previous request
    print '[+] Using scan_id: ' . $scan_id . ' for export.' . PHP_EOL;

    // Schedule the export in .nessus format, taking note of
    // the returned file_id
    // POST /scans/{scan_id}/export
    $file_id = $nessus->scans($scan_id)->export()->setFields(['format' => 'nessus'])->via('post')->file;
    print '[+] Got file_id: ' . $file_id . ' for export job.' . PHP_EOL;

    // We now have to wait for the export to complete. We are
    // just going to check the status of our export every 1 second
    $export_status = 'waiting';
    while ($export_status != 'ready') {

        // Poll for a status update
        $export_status = $nessus->scans($scan_id)->export($file_id)->status()->via('get')->status;
        print '[+] Export status is: ' . $export_status . PHP_EOL;

        // Wait 1 second before another poll
        sleep(1);
    }

    // Once the export == 'ready', download it!
    $file = $nessus->scans($scan_id)->export($file_id)->download()->via('get', true);
    print '[+] Report downloaded.' . PHP_EOL;
    print '[+] Start report sample (first 250 chars): ' . PHP_EOL;
    print substr($file, 0, 250) . PHP_EOL;
    print '[+] End report sample: ' . PHP_EOL;

    // Get the scan details for $scan_id
    // GET /scans/{scan_id}
    $scan_details = $nessus->scans($scan_id)->via('get');
    print '[+] Report name ' . $scan_id . ': ' . $scan_details->info->name . PHP_EOL;
    print '[+] Report targets ' . $scan_id . ': ' . $scan_details->info->targets . PHP_EOL;
    print '[+] Scanner name ' . $scan_id . ': ' . $scan_details->info->scanner_name . PHP_EOL;

    print '[+] Hosts for report ' . $scan_id . PHP_EOL;
    foreach ($scan_details->hosts as $host) {
        print '[+] ' . $host->host_id . ', ' . $host->hostname . ', Severity rating: ' . $host->severity . PHP_EOL;
    }
}

// Create a new scan. For a new scan, we can specify a policy ID
// as the uuid of the scan. Either that or a template UUID is fine.
// For this example we will use a policy, so lets grab the policies.
// Obviously, this requires us to have already set a policy up :>
// GET /policies
$policies = $nessus->policies()->via('get');
if (null !== $policies->policies) {
    foreach ($policies->policies as $policy) {
        print '[+] Policy name ' . $policy->name . ' with UUID ' . $policy->template_uuid . PHP_EOL;
    }
}

// Just take the first policies template uuid
$templates = $nessus->editor('policy')->templates()->via('get');
$template_uuid = $templates->templates[0]->uuid;
print '[+] Will use template_uuid' . $template_uuid . PHP_EOL;

// Add a new scan
// POST /scans
$new_scan = $nessus->scans()
    ->setFields(
        [
            'uuid'     => $template_uuid,
            'settings' => [
                'launch_now'   => false,
                'name'         => 'PHPNessusNG API Test Scan',
                'text_targets' => '127.0.0.1',
            ],
        ]
    )
    ->via('post')->scan;

echo '[+] Configured a new scan ' . $new_scan->id . ' with name ' . $new_scan->name . ' and UUID ' . $new_scan->uuid . PHP_EOL;
echo '[+] Scan ' . $new_scan->uuid . ' will scan ' . $new_scan->custom_targets . PHP_EOL;

// Get some scan detials
$scan_details = $nessus->scans($new_scan->id)->via('get');
echo '[+] Scan ' . $new_scan->id . ' is for scanner ' . $scan_details->info->scanner_name . ' and is ' . $scan_details->info->status . PHP_EOL;

// Launch the scan we have configured and gve it 2 seconds to run.
$launch_scan = $nessus->scans($new_scan->id)->launch()->via('post');
echo '[+] Scan ' . $new_scan->id . ' started with UUID ' . $launch_scan->scan_uuid . PHP_EOL;

// Wait 2 seconds, and re-request the status
while ('running' !== $scan_details->info->status) {
    sleep(0.5);
    $scan_details = $nessus->scans($new_scan->id)->via('get');
}
print '[+] Scan ' . $new_scan->id . ' is for scanner ' . $scan_details->info->scanner_name . ' and is ' . $scan_details->info->status . PHP_EOL;

// Pause the scan
$pause_scan = $nessus->scans($new_scan->id)->pause()->via('post');

// Wait 5 seconds, and re-request the status
while ('paused' !== $scan_details->info->status) {
    sleep(0.5);
    $scan_details = $nessus->scans($new_scan->id)->via('get');
}
print '[+] Scan ' . $new_scan->id . ' is for scanner ' . $scan_details->info->scanner_name . ' and is ' . $scan_details->info->status . PHP_EOL;

// stop the scan
$stop_scan = $nessus->scans($new_scan->id)->stop()->via('post');

// Wait 5 seconds, and re-request the status
while ('canceled' !== $scan_details->info->status) {
    sleep(0.5);
    $scan_details = $nessus->scans($new_scan->id)->via('get');
}
$scan_details = $nessus->scans($new_scan->id)->via('get');
print '[+] Scan ' . $new_scan->id . ' is for scanner ' . $scan_details->info->scanner_name . ' and is ' . $scan_details->info->status . PHP_EOL;

// Delete the scan
$deleted_scan = $nessus->scans($new_scan->id)->via('delete');
print '[+] Deleted scan ' . $new_scan->id . PHP_EOL;

// Sample output

// λ git n6* → php scans.php
// [+] Scans Timestamp: 1413913738
// [+] Scan 63: (123) status: canceled
// [+] Scan 58: (broken scan test) status: completed
// [+] Scan 55: (local scan) status: completed
// [+] Using scan_id: 63 for export.
// [+] Got file_id: 1279697780 for export job.
// [+] Export status is: ready
// [+] Report downloaded.
// [+] Start report sample (first 250 chars):
// <?xml version="1.0" >
// <NessusClientData_v2>
// <Policy><policyName>API Test Policy</policyName>
// <Preferences><ServerPreferences><preference><name>plugin_set</name>
// <value>25451;16066;16775;54191;55364;60374;61980;78528;12878;33572;68768;24384;43101;697
// [+] End report sample:
// [+] Report name 63: 123
// [+] Report targets 63: 127.0.0.1
// [+] Scanner name 63: Local Scanner
// [+] Hosts for report 63
// [+] 2, 127.0.0.1, Severity rating: 25
// [+] Policy name API Test Policy with UUID 731a8e52-3ea6-a291-ec0a-d2ff0619c19d7bd788d6be818b65
// [+] Will use template_uuid731a8e52-3ea6-a291-ec0a-d2ff0619c19d7bd788d6be818b65
// [+] Configured a new scan 132 with name PHPNessusNG API Test Scan and UUID template-8cec9169-56f0-9fc9-663f-309bb3114a9397cd4cc487025ba8
// [+] Scan template-8cec9169-56f0-9fc9-663f-309bb3114a9397cd4cc487025ba8 will scan 127.0.0.1
// [+] Scan 132 is for scanner Local Scanner and is empty
// [+] Scan 132 started with UUID e04319b1-c135-efed-113d-19bc0fcfa81cecbd24f9f3b4d23c
// [+] Scan 132 is for scanner Local Scanner and is running
// [+] Scan 132 is for scanner Local Scanner and is paused
// [+] Scan 132 is for scanner Local Scanner and is canceled
// [+] Deleted scan 132
