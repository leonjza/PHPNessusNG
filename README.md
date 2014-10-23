PHPNessusNG
===========
[![Latest Stable Version](https://poser.pugx.org/leonjza/php-nessus-ng/v/stable.svg)](https://packagist.org/packages/leonjza/php-nessus-ng) [![Total Downloads](https://poser.pugx.org/leonjza/php-nessus-ng/downloads.svg)](https://packagist.org/packages/leonjza/php-nessus-ng) [![Latest Unstable Version](https://poser.pugx.org/leonjza/php-nessus-ng/v/unstable.svg)](https://packagist.org/packages/leonjza/php-nessus-ng) [![License](https://poser.pugx.org/leonjza/php-nessus-ng/license.svg)](https://packagist.org/packages/leonjza/php-nessus-ng)

PHP wrapper functions for interfacing with the Nessus **V6.x** API.

If you are looking for the Nessus V5.x capable XMLRPC API Class, please see the `n5` branch [here](https://github.com/leonjza/PHPNessusNG/tree/n5)

Information:
-----------
The Nessus 6 Vulnerability Scanner provides a RESTful API interface. This library aims to be a wrapper around this API, allowing you to query it directly, as detailed in the API documentation.

One major advantage of this library is that it does not necessarily have to update when new endpoints are made available. It is simply a wrapper. Calls to the API can be made exactly as it is documented in the API reference found at the `/api` resource of your local scanner. For eg. If a API endpoint is defined as:

```text
DELETE /scans/{scan_id}
```

Then you can call it with:

```php
$client->scans($id)->via('delete');
```

The latest version of this wrapper has only been tested against a Nessus **6.0.0** scanner.

Concepts:
---------
There are a fair number of ways to use this library. All methods start the same way though; Instantiating a new instance. The library will handle the authentication cookie automatically internally.

Some examples of calling the API:

```php
<?php

include 'vendor/autoload.php';

$t = new Nessus\Client('username', 'password', 'hostname.local');
```

Now, you may call API methods either via method chaining, or via the `call()`, method.  
A method chaining example (assuming `$scan_id` == 5) would be:

```php
// Get a file ID for a new report export
$file_id = $t->scans($scan_id)->export()->setFields(array('format' => 'nessus'))->via('post');
```

The same using the `call()` method would be:

```php
// Get a file ID for a new report export
$t->setFields(array('format' => 'nessus'));
$t->call('scans/5/export/');
$file_id = $t->via('post');
```

**NOTE**: All calls should end with a `via($method)`, where $method is the HTTP method to use. `via()` accepts a second argument, which specifies if a response should be returned raw if true, or a parsed JSON object if not set (false).

Installation:
------------
The easiest way by far would be to install the library via composer. Add the following line to your `composer.json`:

```json
"leonjza/php-nessus-ng": "dev-n6"
```

Run `php composer.phar update`. You should now have the `\Nessus` class available to use.

Usage example:
---------------
Include the Composer Autoloader, instantiate a new instance, and start using it. Below is an example script that will download the first available report in the `.nessus` format:

```php
<?php

include 'vendor/autoload.php';

$t = new Nessus\Client('username', 'password', 'hostname.local');

// Get a scan_id to export a report for.
$scan_id = $t->scans()->via('get')->scans[0]->id;

// Request the export, taking note of the returned file_id that we need.
$file_id = $t->scans($scan_id)->export()->setFields(array('format' => 'nessus'))->via('post')->file;

// Set a status that will update as we poll for a status
$export_status = 'waiting';

// If the export status is ready, break.
while ($export_status != 'ready') {

    // Poll for a status update
    $export_status = $t->scans($scan_id)->export($file_id)->status()->via('get')->status;

    // Wait 1 second before another poll
    sleep(1);
}

// Get the .nessus report export, specifying that we want it via a raw get
$file = $t->scans($scan_id)->export($file_id)->download()->via('get', true);
```

Contact
-------
Twitter: [@leonjza](https://twitter.com/leonjza)
