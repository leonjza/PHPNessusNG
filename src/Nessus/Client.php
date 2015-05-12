<?php
/*
The MIT License (MIT)

Copyright (c) 2014 Leon Jacobs

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

namespace Nessus;

/**
 * PHP Nessus NG
 *
 * @package  PHPNessusNG
 * @author   Leon Jacobs <@leonjza>
 * @license  MIT
 * @link     https://leonjza.github.io/
 */

use Nessus\Nessus;
use Nessus\Exception;

/**
 * Class Client
 */

Class Client
{

    /**
     * @var string
     */
    public $version = '1.0.3';

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $host;

    /**
     * @var string
     */
    public $port;

    /**
     * @var bool
     */
    public $https;

    /**
     * @var bool
     */
    public $validate_cert = false;

    /**
     * @var string
     */
    public $url;

    /**
     * @var array
     */
    public $fields = array();

    /**
     * @var int
     */
    public $timeout = 10;

    /**
     * @var string
     */
    public $token = null;

    /**
     * @var string
     */
    public $call;

    /**
     * @var bool
     */
    public $raw = false;

    /**
     * @var bool
     */
    public $use_proxy = false;

    /**
     * @var string
     */
    public $proxy_host = null;

    /**
     * @var int
     */
    public $proxy_port = null;

    /**
     * @var string
     */
    public $proxy_user;

    /**
     * @var string
     */
    public $proxy_pass;

    /**
     * Creates a new \Nessus\Client Object
     *
     * @param   string $user  The username to authenticate with
     * @param   string $pass  The password to authenticate with
     * @param   string $host  The Nessus Scanner
     * @param   string $port  The The port the Nessus Scanner is listening on
     * @param   bool   $https Should the connection be via HTTPs
     *
     * @return void
     */
    public function __construct($user, $pass, $host, $port = 8834, $https = true)
    {

        // Set the values we have received
        $this->username = $user;
        $this->password = $pass;
        $this->host = $host;
        $this->port = $port;
        $this->https = $https;

        // Construct the Base Url to use
        $this->url = ($this->https ? 'https://' : 'http://');
        $this->url .= $this->host;
        $this->url .= ':'. $this->port . '/';

        // Check that we have a valid host
        if (!filter_var($this->url, FILTER_VALIDATE_URL))
            throw new Exception\InvalidUrl($this->url . ' appears to be unparsable.');
    }

    /**
     * Mutator method to change the certificate validation rule
     *
     * @param   bool $validate True if the server SSL certificate should be validated. False if not.
     *
     * @return  $this
     */
    public function validateCert($validate = true)
    {
        $this->validate_cert = $validate;
        return $this;
    }

    /**
     * Set the configuration options needed to use a proxy server for
     * requests to the Nessus API
     *
     * @param   string $host     The proxy server
     * @param   int    $port     The port the proxy server is listening on
     * @param   string $username The username to authenticate with if needed
     * @param   string $password The password to authenticate with if needed
     *
     * @return  $this
     */
    public function configureProxy($host, $port, $username = null, $password = null)
    {

        // Check port validity
        if (!is_int($port) || $port <= 0 || $port > 65535)
            throw new Exception\ProxyError('Invalid proxy port of ' . $port . ' specified.');

        // Ensure that we have proxy host:port defined
        if (is_null($host) || is_null($port))
            throw new Exception\ProxyError('A host and port specification is required for proxy use.');

        $this->proxy_host = $host;
        $this->proxy_port = $port;
        $this->proxy_user = $username;
        $this->proxy_pass = $password;

        return $this;
    }

    /**
     * Mutator method to set the proxy server usage
     *
     * @param   bool $use Specify the use of the proxy server via true
     *
     * @return  $this
     */
    public function useProxy($use = true)
    {

        // Ensure that we have proxy host:port defined
        if (is_null($this->proxy_host) || is_null($this->proxy_port))
            throw new Exception\ProxyError('A host and port specification is required for proxy use.');

        $this->use_proxy = $use;

        return $this;
    }

    /**
     * Set the API call location
     *
     * @param   string $location The api endpoint to call.
     *
     * @return  $this
     */
    public function call($location)
    {

        // Remove the first slash if its there
        $location = ltrim($location, '/');
        $this->call = $location;

        return $this;
    }

    /**
     * Magic method to allow API calls to be constructe via
     * method chainging. ie: $call->server()->properties() will
     * result in a endpoint location of BASE_URL/server/properties/
     *
     * Magic method arguments will also be parsed as part of the call.
     * ie: $call->make('server', 'properties') will result in a
     * endpoint location of BASE_URL/server/properties/
     *
     * @param   string $location The api endpoint to call.
     * @param   string $slug     Any arguments to parse as part of the location
     *
     * @return  $this
     */
    public function __call($location, $slug = null)
    {

        // Ensure the location is lowercase
        $this->call .= strtolower($location) . '/';

        if (count($slug) > 0)
            foreach ($slug as $slug_value)
                $this->call .= $slug_value . '/';

        return $this;
    }

    /**
     * Specify any fields that should form part of say a POST
     * request.
     *
     * @param   array $fields The key=>value's of the fields to send with
     *
     * @return  $this
     */
    public function setFields($fields = array())
    {
        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    /**
     * Make a API call using the $method described. This is the final method
     * that should be called to make requests. Unless $raw is set to true,
     * the response will be a PHP \Object
     *
     * @param   string $method The HTTP method that should be used for the call
     * @param   bool   $raw    Should the response be raw JSON
     *
     * @throws Exception
     *
     * @return  $this
     */
    public function via($method = 'get', $raw = false)
    {
        $method = strtolower($method);

        if ($raw)
            $this->raw = true;

        $valid_requests = array('get', 'post', 'put', 'delete');
        if (!in_array($method, $valid_requests))
            throw new Exception\InvalidMethod("Invalid HTTP method '" . $method . "' specified.");

        // Make the call
        $api_call = new Nessus\Call();
        try
        {
            $api_response = $api_call->call($method, $this);
        } catch(\Exception $error)
        {
            // Catch and re-throw this exception to allow us to reset the request
            // so that the client can continue to be used even after this failed request.
            $this->resetRequest();
            throw $error;
        }

        $this->resetRequest();

        return $api_response;
    }

    /**
     * Method resets the outgoing request so that future requests are not appended.
     *
     * @return void
     */
    protected function resetRequest()
    {
        // Clear call, raw & fields so a new request is fresh
        $this->call = null;
        $this->fields = array();
        $this->raw = false;
    }
}
