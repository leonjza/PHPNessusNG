PHPNessusNG
===========

PHP wrapper functions for interfacing with the Nessus API

Information:
-----------
The Nessus Vulnerability Scanner provides an API interface via XMLRPC.
See: http://static.tenable.com/documentation/nessus_5.0_XMLRPC_protocol_guide.pdf

The latest version of this wrapper has only been tested against a Nessus 5.2.1 scanner.

This class is simply a set of functions implemented using PHP-Curl to enable querying of this
API using a function and then receiving an array with the applicable data.

Installation:
------------
The easiest way by far would be to install the library via composer. Add the followig line to your `composer.json`:

```json
"leonjza/php-nessus-ng": "*"
```


Usage example:
---------------

Ensure you have the Composer AUtoloader included, then, create a new api instance with:


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

Do some API calls. Most methods return some usefull information that should be inspected in your usage case.

```php
try {

    $api->feed();
    $api->reportList();
    $api->policyList();
    $api->scanList();

} catch(Exception $e) {

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

Known issues:
-------------
-	There are probably bugs about.
-	Not all API call have been implemented. Coming soon(tm) as I need them.
-	Probably lack of proper documentation too.

Contact
-------
Twitter: @leonjza
