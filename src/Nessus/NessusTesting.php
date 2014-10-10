<?php
/**
 * PHPNessusAPI
 *
 * PHP Version 5
 *
 * @category Library
 * @package  PHPNessusAPI
 * @author   Leon Jacobs <@leonjza>
 * @license  MIT
 * @link     @leonjza
 */

namespace Nessus;

/**
 * This class will handle all off the testing of the Nessus API related functions.
 *
 * Tests will be typically run by instantiating a new Nessus\NessusTesting instance
 * and running it with $handler->runTests();
 *
 * Sample:
 *
 *  <?php
 *
 *  include 'vendor/autoload.php';  // Include autoloader from a composer install
 *
 *  $handler = new Nessus\NessusTesting("https://hostname.net", 8834, "username", "password");
 *  $handler->runTests();
 *
 *
 * @category Library
 * @package  PHPNessusAPI
 * @author   Leon Jacobs <@leonjza>
 * @license  MIT
 * @link     @leonjza
 */
class NessusTesting extends NessusInterface
{

    private $failure_count = 0;
    private $success_count = 0;
    private $scheduled_scan_template_uuid = null;
    private $running_scan_uuid = null;

    /**
     * A custom error handler to report on variables that are not defined. This speaks
     * mostly of stuff that has changed/are missing
     *
     * @param int    $errno   The host to which we should connect.
     * @param string $errstr  The port to which we should connect.
     * @param string $errfile The username
     * @param string $errline The that would be used.
     *
     * @return multi An approprite exception
     */
    public function TestingErrorHandler($errno, $errstr, $errfile, $errline)
    {

        $errors = array(
            'Undefined index:',
            'Undefined variable',
            'Undefined property',
            'Trying to get property of non-object',
            'Undefined offset',
            'Illegal string offset'
        );

        // Loop through $errors and throw \Exception if it matches
        foreach ($errors as $error)

            if (strpos($errstr, $error) !== False)
                //We throw an exception that will be caught in the test
                throw new \Exception($errstr . ' in ' . $errfile . ' line: ' . $errline, $errno);

        return false;
    }

    /**
     * Instantiate the testing instance. We will define a custom error handler
     * and proceed to run the parent __construct()
     *
     * @param string $url      The host to which we should connect.
     * @param string $port     The port to which we should connect.
     * @param string $username The username
     * @param string $password The that would be used.
     *
     * @return void
     */
    public function __construct($url, $port, $username, $password)
    {

        // Init
        self::writeInfo("Starting NessusTesting and setting our testing error handler...");

        // Test if this has been invoked via the cli
        if (php_sapi_name() !== 'cli')
            throw new \Exception("These tests should be run from the commandine only!", 1);

        // Warning message
        self::writeWarning("[READ ME]");
        self::writeWarning("This test is going to schedule a scan for 127.0.0.0/8 1 year from now.");
        self::writeWarning("As part of the test, we will launch, pause, resume and stop the scan.");
        self::writeWarning("Please make sure that you cleanup after the test should any of the steps fail!");
        self::writeInfo("Waiting 5s for user cancel, else we will continue with the testing...");
        sleep(5);

        // Set a temp error handler
        set_error_handler(array($this, 'TestingErrorHandler'));

        // Call the parent __construct()
        try {

            parent::__construct($url, $port, $username, $password);
        } catch (\Exception $e) {

            self::writeError("Setup Failed! Make sure the server is available and your credentails are correct!");
            throw new \Exception($e->getMessage(), 1);

        }
    }

    /**
     * Print Informational string with the current timestamp
     *
     * @param string $string The string that should be printed
     *
     * @return void
     */
    private function writeInfo($string = '')
    {

        print "[INFO] " . \Carbon\Carbon::now()->toDateTimeString() . " - " . $string . PHP_EOL;
        return;
    }

    /**
     * Print OK string with the current timestamp
     *
     * @param string $string The string that should be printed
     *
     * @return void
     */
    private function writeOk($string = '')
    {

        print "\033[1;32m" . "[OK] " . \Carbon\Carbon::now()->toDateTimeString() . " - " . $string . "\033[0m" . PHP_EOL;
        return;
    }

    /**
     * Print Warning string with the current timestamp
     *
     * @param string $string The string that should be printed
     *
     * @return void
     */
    private function writeWarning($string = '')
    {

        print "\033[1;33m" . "[WARNING] " . \Carbon\Carbon::now()->toDateTimeString() . " - " . $string . "\033[0m" . PHP_EOL;
        return;
    }

    /**
     * Print Error string with the current timestamp
     *
     * @param string $string The string that should be printed
     *
     * @return void
     */
    private function writeError($string = '')
    {

        print "\033[0;31m" . "[ERROR] " . \Carbon\Carbon::now()->toDateTimeString() . " - " . $string . "\033[0m" . PHP_EOL;
        return;
    }

    /**
     * Run the Tests
     *
     * @return void
     */
    public function runTests()
    {

        // Getting here means we have successfully connected
        self::writeOk("Connected. Token: " . $this->token);

        // Run the tests
        try {

            // Basic Tests
            $this->testfeed();
            $this->testpolicyList();

            // Schedule a scan
            $this->testnewScanTemplate();

            self::writeInfo("Template ID from testnewScanTemplate(): " . $this->scheduled_scan_template_uuid);

            // Test the templateList()
            $this->testtemplateList();

            // Start, check the scanList(), Pause, Resume & Stop the scan
            $this->testtemplateLaunch();
            $this->testscanList();
            $this->testscanPause();
            $this->testscanResume();
            $this->testscanStop();

            // Delete the Template
            $this->testtemplateDelete();

            // Last Tests
            $this->testreportList();
            $this->testserverLoad();

        } catch (\Exception $e) {

            $this->writeError("Testing Failed! " . $e->getMessage());
        }

        print "\n";
        self::writeOk("Successful Tests: " . $this->success_count);

        if ($this->failure_count > 0)
            self::writeWarning("Failed Tests: " . $this->failure_count);

        self::writeInfo("Done.");

    }

    /**
     * Run the feed() test
     *
     * @return void
     */
    private function testfeed() {

        self::writeInfo("Testing feed()...");
        try {

            // Do the call
            $test = $this->feed();

            // Just writeInfo the results as its usefull to know the versions when reporting
            self::writeInfo("Feed: " . $test['feed']);
            self::writeInfo("Nessus Type: " . $test['nessus_type']);
            self::writeInfo("Server Version: " . $test['server_version']);
            self::writeInfo("Web Server Version: " . $test['web_server_version']);
            self::writeInfo("Nessus UI Version: " . $test['nessus_ui_version']);
            self::writeInfo("Expiration: " . $test['expiration']);
            self::writeInfo("MSP: " . $test['msp']);
            self::writeInfo("Loaded Plugin Set: " . $test['loaded_plugin_set']);
            self::writeInfo("Expiration Time: " . $test['expiration_time']);
            self::writeInfo("Plugin Rules: " . $test['plugin_rules']);
            self::writeInfo("Report Email: " . $test['report_email']);
            self::writeInfo("Tags: " . $test['tags']);
            self::writeInfo("Diff: " . $test['diff']);
            self::writeInfo("Multi Scanner: " . $test['multi_scanner']);

            self::writeOk("feed() testing passed.");
            $this->success_count++;

        } catch (\Exception $e) {

            self::writeError("feed() test failed. Error: " . $e->getMessage());
            $this->failure_count++;
        }
    }

    /**
     * Run the reportList() test
     *
     * @return void
     */
    private function testreportList() {

        self::writeInfo("Testing reportList()...");
        try {

            // Do the call
            $test = $this->reportList();

            // Test if 'reports' is set
            if (!isset($test['reports']))
                self::writeWarning("Array key 'reports' is not set. It could mean that there are 0 reports for this user. Rest of the tests will not run.");

            if (isset($test['reports'])) {

                self::writeInfo("Received " . count($test['reports']) . " reports. Trying to access values from the first one...");
                $first_key = key($test['reports']);

                self::writeInfo(
                    "Report 1 has (name, status, readableName, timestamp) ("
                        . $first_key . ", "
                        . $test['reports'][$first_key]['status'] . ", "
                        . $test['reports'][$first_key]['readableName'] . ", "
                        . $test['reports'][$first_key]['timestamp']
                        . ")"
                );
            }

            self::writeOk("reportList() testing passed.");
            $this->success_count++;

        } catch (\Exception $e) {

            self::writeError("reportList() test failed. Error: " . $e->getMessage());
            $this->failure_count++;
        }
    }

    /**
     * Run the policyList() test
     *
     * @return void
     */
    private function testpolicyList() {

        self::writeInfo("Testing policyList()...");
        try {

            // Do the call
            $test = $this->policyList();

            // Test if 'policies' is set
            if (!isset($test['policies']))
                self::writeWarning("Array key 'policies' is not set. It could mean that there are 0 reports for this user. Rest of the tests will not run.");

            if (isset($test['policies'])) {

                self::writeInfo("Received " . count($test['policies']) . " policies. Trying to access values from the first one...");
                $first_key = key($test['policies']);

                self::writeInfo(
                    "Policy 1 has (id, readableName) ("
                        . $first_key . ", "
                        . $test['policies'][$first_key]
                        . ")"
                );
            }

            self::writeOk("policyList() testing passed.");
            $this->success_count++;

        } catch (\Exception $e) {

            self::writeError("policyList() test failed. Error: " . $e->getMessage());
            $this->failure_count++;
        }
    }

    /**
     * Run the scanList() test
     *
     * @return void
     */
    private function testscanList() {

        self::writeInfo("Testing scanList()...");
        try {

            // Do the call
            $test = $this->scanList();

            // Test if anything is returned
            if (count($test) <= 0)
                self::writeWarning("Empty response for scanList(). It could mean that there are 0 scans for this user. Rest of the tests will not run.");

            if (count($test) > 0) {

                self::writeInfo("Received " . count($test) . " scans. Trying to access values from the first one...");
                $first_key = key($test);

                self::writeInfo(
                    "Scan 1 has (uuid, completion_current, completion_total, readablename, status, start_time) ("
                        . $first_key . ", "
                        . $test[$first_key]['completion_current'] . ", "
                        . $test[$first_key]['completion_total'] . ", "
                        . $test[$first_key]['readablename'] . ", "
                        . $test[$first_key]['status'] . ", "
                        . $test[$first_key]['start_time'] . ", "
                        . ")"
                );
            }

            self::writeOk("scanList() testing passed.");
            $this->success_count++;

        } catch (\Exception $e) {

            self::writeError("scanList() test failed. Error: " . $e->getMessage());
            $this->failure_count++;
        }
    }

    /**
     * Run the templateList() test
     *
     * @return void
     */
    private function testtemplateList() {

        self::writeInfo("Testing templateList()...");
        try {

            // Do the call
            $test = $this->templateList();

            // Test if anything is returned
            if (count($test) <= 0)
                self::writeWarning("Empty response for templateList(). It could mean that there are 0 templates for this user. Rest of the tests will not run.");

            if (count($test) > 0) {

                self::writeInfo("Received " . count($test) . " scans. Trying to access values from the first one...");
                $first_key = key($test);

                self::writeInfo(
                    "Template 1 has (policy_name, type, rrules, starttime, name, uuid, owner, shared, user_permissions, timestamp,last_modification_date, creation_date, owner_id, id) ("
                        . $first_key . ", "
                        . $test[$first_key]['policy_name'] . ", "
                        . $test[$first_key]['type'] . ", "
                        . (isset($test[$first_key]['rrules']) ? $test[$first_key]['rrules'] : "Not Set") . ", " # Some templates can have no schedule
                        . $test[$first_key]['starttime'] . ", "
                        . $test[$first_key]['name'] . ", "
                        . $test[$first_key]['uuid'] . ", "
                        . $test[$first_key]['owner'] . ", "
                        . $test[$first_key]['shared'] . ", "
                        . $test[$first_key]['user_permissions'] . ", "
                        . $test[$first_key]['timestamp'] . ", "
                        . $test[$first_key]['last_modification_date'] . ", "
                        . $test[$first_key]['creation_date'] . ", "
                        . $test[$first_key]['owner_id'] . ", "
                        . $test[$first_key]['id'] . ", "
                        . ")"
                );
            }

            self::writeOk("templateList() testing passed.");
            $this->success_count++;

        } catch (\Exception $e) {

            self::writeError("templateList() test failed. Error: " . $e->getMessage());
            $this->failure_count++;
        }
    }

    /**
     * Run the newScanTemplate() test
     *
     * @return void
     */
    private function testnewScanTemplate() {

        self::writeInfo("Testing newScanTemplate()...");
        try {

            self::writeInfo("Preparing some fake data for a scan...");

            // First, we need to prepare some premade data
            $template_name = "PHPNessusAPI " . self::$version . " Function Test";

            // We will just scan 127.0.0.0/8 for testing purposes
            $target = "127.0.0.0/8";

            // Choose now + 1 year for the test scheduled scan.
            $starttime = \Carbon\Carbon::now('UTC')->addYear();

            // Parse the date into something that Nessus Understands, ie 20150819T155405+0000
            $parsedtime = $starttime->year
                . sprintf('%02d', $starttime->month)
                . sprintf('%02d', $starttime->day)
                . 'T'
                . sprintf('%02d', $starttime->hour)
                . sprintf('%02d', $starttime->minute)
                . sprintf('%02d', $starttime->second)
                . '0000';

            // Ask for a policyID
            self::writeInfo("Asking Nessus for a Policy ID to use...");

            $policy = $this->policyList();
            $policy_id = key($policy['policies']);

            self::writeInfo("Will use PolicyID " . $policy_id . " called " . $policy['policies'][$policy_id]);

            self::writeWarning(
                "Scheduling a scan to start at " . $starttime->toDateTimeString() . " for " .
                $target . " using a template name of '" . $template_name . "'"
            );

            // Do the call
            $test = $this->newScanTemplate($template_name, $policy_id, $target, $parsedtime);

            // Test if everything is OK
            if (!isset($test['response'])) {

                self::writeWarning("Empty response for newScanTemplate(). Investigate this!");
                $this->failure_count++;

                return;
            }

            // Print the response
            self::writeInfo(
                "Scheduled scan details (name, policy_id, readableName, owner, target, rRules, startTime) ("
                    . $test['response']['name'] . ", "
                    . $test['response']['policy_id'] . ", "
                    . $test['response']['readableName'] . ", "
                    . $test['response']['owner'] . ", "
                    . $test['response']['target'] . ", "
                    . $test['response']['rRules'] . ", "
                    . $test['response']['startTime'] . ", "
                    . ")"
            );

            $this->scheduled_scan_template_uuid = $test['response']['name'];

            self::writeOk("newScanTemplate() testing passed.");
            $this->success_count++;

        } catch (\Exception $e) {

            self::writeError("newScanTemplate() test failed. Error: " . $e->getMessage());
            $this->failure_count++;
        }
    }

    /**
     * Run the templateLaunch() test
     *
     * @return void
     */
    private function testtemplateLaunch() {

        self::writeInfo("Testing templateLaunch()...");
        try {

            // Do the call
            $test = $this->templateLaunch($this->scheduled_scan_template_uuid);

            self::writeInfo(
                "Started template " . $this->scheduled_scan_template_uuid
                . ". Nessus response was (start_time, status, name, uuid, owner, type, id) ("
                . $test['response']['start_time'] . ", "
                . $test['response']['status'] . ", "
                . $test['response']['name'] . ", "
                . $test['response']['uuid'] . ", "
                . $test['response']['owner'] . ", "
                . $test['response']['type'] . ", "
                . $test['response']['id'] . ", "
                . ")"
            );

            // Set the UUID we got from the template launch
            $this->running_scan_uuid = $test['response']['uuid'];

            self::writeOk("templateLaunch() testing passed.");
            $this->success_count++;

        } catch (\Exception $e) {

            self::writeError("templateLaunch() test failed. Error: " . $e->getMessage());
            $this->failure_count++;
        }
    }

    /**
     * Run the scanPause() test
     *
     * @return void
     */
    private function testscanPause() {

        self::writeInfo("Testing scanPause()...");
        try {

            self::writeInfo("Sleeping for 3s to allow Nessus to do 'things'...");
            sleep(3);

            // Do the call
            $test = $this->scanPause($this->running_scan_uuid);

            self::writeInfo(
                "Paused scan " . $this->running_scan_uuid
                . ". Nessus response was (readableName, start_time, status, name, uuid, shared, user_permissions, default_permisssions, owner, owner_id, last_modification_date, creation_date, type, id) ("
                . $test['response']['readableName'] . ", "
                . $test['response']['start_time'] . ", "
                . $test['response']['status'] . ", "
                . $test['response']['name'] . ", "
                . $test['response']['uuid'] . ", "
                . $test['response']['shared'] . ", "
                . $test['response']['user_permissions'] . ", "
                . $test['response']['default_permisssions'] . ", "
                . $test['response']['owner'] . ", "
                . $test['response']['owner_id'] . ", "
                . $test['response']['last_modification_date'] . ", "
                . $test['response']['creation_date'] . ", "
                . $test['response']['type'] . ", "
                . $test['response']['id'] . ", "
                . ")"
            );

            self::writeOk("scanPause() testing passed.");
            $this->success_count++;

        } catch (\Exception $e) {

            self::writeError("scanPause() test failed. Error: " . $e->getMessage());
            $this->failure_count++;
        }
    }

    /**
     * Run the scanResume() test
     *
     * @return void
     */
    private function testscanResume() {

        self::writeInfo("Testing scanResume()...");
        try {

            self::writeInfo("Sleeping for 3s to allow Nessus to do 'things'...");
            sleep(3);

            // Do the call
            $test = $this->scanResume($this->running_scan_uuid);

            self::writeInfo(
                "Paused scan " . $this->running_scan_uuid
                . ". Nessus response was (readableName, start_time, status, name, uuid, shared, user_permissions, default_permisssions, owner, owner_id, last_modification_date, creation_date, type, id) ("
                . $test['response']['readableName'] . ", "
                . $test['response']['start_time'] . ", "
                . $test['response']['status'] . ", "
                . $test['response']['name'] . ", "
                . $test['response']['uuid'] . ", "
                . $test['response']['shared'] . ", "
                . $test['response']['user_permissions'] . ", "
                . $test['response']['default_permisssions'] . ", "
                . $test['response']['owner'] . ", "
                . $test['response']['owner_id'] . ", "
                . $test['response']['last_modification_date'] . ", "
                . $test['response']['creation_date'] . ", "
                . $test['response']['type'] . ", "
                . $test['response']['id'] . ", "
                . ")"
            );

            self::writeOk("scanResume() testing passed.");
            $this->success_count++;

        } catch (\Exception $e) {

            self::writeError("scanResume() test failed. Error: " . $e->getMessage());
            $this->failure_count++;
        }
    }

    /**
     * Run the scanStop() test
     *
     * @return void
     */
    private function testscanStop() {

        self::writeInfo("Testing scanStop()...");
        try {

            self::writeInfo("Sleeping for 3s to allow Nessus to do 'things'...");
            sleep(3);

            // Do the call
            $test = $this->scanStop($this->running_scan_uuid);

            self::writeInfo(
                "Paused scan " . $this->running_scan_uuid
                . ". Nessus response was (readableName, start_time, status, name, uuid, shared, user_permissions, default_permisssions, owner, owner_id, last_modification_date, creation_date, type, id) ("
                . $test['response']['readableName'] . ", "
                . $test['response']['start_time'] . ", "
                . $test['response']['status'] . ", "
                . $test['response']['name'] . ", "
                . $test['response']['uuid'] . ", "
                . $test['response']['shared'] . ", "
                . $test['response']['user_permissions'] . ", "
                . $test['response']['default_permisssions'] . ", "
                . $test['response']['owner'] . ", "
                . $test['response']['owner_id'] . ", "
                . $test['response']['last_modification_date'] . ", "
                . $test['response']['creation_date'] . ", "
                . $test['response']['type'] . ", "
                . $test['response']['id'] . ", "
                . ")"
            );

            self::writeOk("scanStop() testing passed.");
            $this->success_count++;

        } catch (\Exception $e) {

            self::writeError("scanStop() test failed. Error: " . $e->getMessage());
            $this->failure_count++;
        }
    }

    /**
     * Run the templateDelete() test
     *
     * @return void
     */
    private function testtemplateDelete() {

        self::writeInfo("Testing templateDelete()...");
        try {

            self::writeInfo("Sleeping for 3s to allow Nessus to do 'things'...");
            sleep(3);

            // Do the call
            $test = $this->templateDelete($this->scheduled_scan_template_uuid);

            self::writeInfo(
                "Paused scan " . $this->running_scan_uuid
                . ". Nessus response was (name, policy_id, readableName, owner, target, rRules, startTime) ("
                . $test['response']['name'] . ", "
                . $test['response']['policy_id'] . ", "
                . $test['response']['readableName'] . ", "
                . $test['response']['owner'] . ", "
                . $test['response']['target'] . ", "
                . $test['response']['rRules'] . ", "
                . $test['response']['startTime'] . ", "
                . ")"
            );

            self::writeOk("templateDelete() testing passed.");
            $this->success_count++;

        } catch (\Exception $e) {

            self::writeError("templateDelete() test failed. Error: " . $e->getMessage());
            $this->failure_count++;
        }
    }

    /**
     * Run the serverLoad() test
     *
     * @return void
     */
    private function testserverLoad() {

        self::writeInfo("Testing serverLoad()...");
        try {

            // Do the call
            $test = $this->serverLoad();

            // Just writeInfo the results as its usefull to know the versions when reporting
            self::writeInfo("Feed: " . $test['platform']);
            self::writeInfo("Nessus Type: " . $test['num_scans']);
            self::writeInfo("Server Version: " . $test['num_sessions']);
            self::writeInfo("Web Server Version: " . $test['num_hosts']);
            self::writeInfo("Nessus UI Version: " . $test['num_tcp_sessions']);
            self::writeInfo("Expiration: " . $test['loadavg']);

            self::writeOk("serverLoad() testing passed.");
            $this->success_count++;

        } catch (\Exception $e) {

            self::writeError("serverLoad() test failed. Error: " . $e->getMessage());
            $this->failure_count++;
        }
    }
}