PHPNessusNG
===========

PHP wrapper functions for interfacing with the Nessus API

Information:
-----------
The Nessus Vulnerability Scanner provides an API interface via XMLRPC.
See: http://static.tenable.com/documentation/nessus_5.0_XMLRPC_protocol_guide.pdf

The latest version of this wrapper has only been tested against a Nessus 5.2.5 scanner.

Installation:
------------
The easiest way by far would be to install the library via composer. Add the followig line to your `composer.json`:

```json
"leonjza/php-nessus-ng": "*"
```

Run `php composer.phar update`. You should now have the `\Nessus\NessusInterface()` class available to use.

Usage example:
---------------

Ensure you have the Composer Autoloader included, then, create a new api instance with:

```php
try {

    $api = new \Nessus\NessusInterface(
        $__url,
        $__port,
        $__username,
        $__password
    );

} catch(\Exception $e) {

    preprint($e->getMessage());
}
```

Once you have instantiated the class, do some calls:

```php
try {

    $api->feed();
    $api->reportList();
    $api->policyList();
    $api->scanList();

} catch(\Exception $e) {

    preprint($e->getMessage());
}
```

Current Available Methods
-------------------------

    [1] => reportList
    [2] => feed
    [3] => policyList
    [4] => scanList
    [5] => templateList
    [6] => newScanTemplate
    [7] => scanPause
    [8] => scanResume
    [9] => scanStop
    [10] => templateDelete
    [11] => templateLaunch
    [12] => serverLoad
    
Contact
-------
Twitter: @leonjza
