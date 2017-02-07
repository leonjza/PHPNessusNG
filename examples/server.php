<?php

include __DIR__ . '/../vendor/autoload.php';

// Prepare the connection to the API
$nessus = new Nessus\Client('username', 'password', '192.168.56.101');

// Get the Server properties
// GET /server/properties
$server_properties = $nessus->server()->properties()->via('get');

print '[+] Server Version: ' . $server_properties->server_version . PHP_EOL;
print '[+] Server Build: ' . $server_properties->server_build . PHP_EOL;
print '[+] UI Version: ' . $server_properties->nessus_ui_version . PHP_EOL;
foreach ($server_properties->notifications as $notification) {
    print '[+] Notification Type: ' . $notification->type . ' : ' . $notification->message . PHP_EOL;
}

// Get the server status
// GET /server/status
$server_status = $nessus->server()->status()->via('get');
print '[+] Server Progress: ' . $server_status->progress . PHP_EOL;
print '[+] Server Status: ' . $server_status->status . PHP_EOL;


// Sample output
// λ git n6* → php server.php
// [+] Server Version: 6.0.0
// [+] Feed: ProFeed
// [+] Notification Type: warning : Your plugin feed subscription will expire in 26 day(s).
// [+] Server Progress:
// [+] Server Status: ready
