PHPNessusNG
===========

PHP wrapper functions for interfacing with the Nessus API

Information:
-----------
The Nessus Vulnerability Scanner provides an API interface via XMLRPC.
See: http://static.tenable.com/documentation/nessus_5.0_XMLRPC_protocol_guide.pdf

The latest version of this wrapper has only been tested against a Nessus 5.2.7 scanner.

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

    print $e->getMessage();
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

    print $e->getMessage();
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
    [13] => reportDownload
    [14] => reportHosts
    
Implementing Your Own Logging
-----------------------------

PHPNessusNG has a `logRequest()` internal that will fire for each successful request. By default it simply returns void, however you can modify its behaviour by simply extending the base class and redefining the `logRequest()` method:

```php
class Nessus extends \Nessus\NessusInterface
{

    /**
     * Log API requests to the Applications General Log
     *
     * @return void
     */
    public function logRequest()
    {

        \Logger::info('Nessus API Call Made to: ' . $this->call);
    }

}
```
    
Testing
-------

Testing the Library can be done by instantiating a new `Nessus\NessusTesting` object instead of `Nessus\NessusInterface`. We can then run the test with `$handler->runTests()`:

```php
<?php

include 'vendor/autoload.php';  // Include autoloader from a composer install

$handler = new Nessus\NessusTesting("https://hostname.net", 8834, "username", "password");
$handler->runTests();
```

This _should_ leave you with output similar to:

```bash
[...]
[OK] 2014-08-20 08:58:35 - Successful Tests: 11
[WARNING] 2014-08-20 08:58:35 - Failed Tests: 1
[INFO] 2014-08-20 08:58:35 - Done.
```

Contact
-------
Twitter: [@leonjza](https://twitter.com/leonjza)
