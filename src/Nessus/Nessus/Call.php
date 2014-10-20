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

namespace Nessus\Nessus;

/**
 * PHP Nessus NG
 *
 * @package  PHPNessusNG
 * @author   Leon Jacobs <@leonjza>
 * @license  MIT
 * @link     https://leonjza.github.io/
 */

use Nessus\Exception;
use Requests;
use Requests_Session;

/**
 * Class Client
 */

Class Call
{

    /**
     * Authenticates to a Nessus Scanner, saving the token that was received
     *
     * @param  object $scope The scope injected from a \Nessus\Client
     *
     * @return string
     */
    public function token($scope)
    {

        // If the token is not defined, authenticate and get a new one
        if (is_null($scope->token)) {

            // Specify the no_token arg as true as this is kinda what
            // we are doing here.
            $response = $this->call('post', $scope, true);

            // Set the determined token
            $scope->token = $response->token;
        }

        return $scope->token;
    }

    /**
     * Makes an API call to a Nessus Scanner
     *
     * @param  string $methid   The method that should be used in the HTTP request
     * @param  object $scope    The scope injected from a \Nessus\Client
     * @param  bool   $no_token Should a token be used in this request
     *
     * @return string
     */
    public function call($method, $scope, $no_token = false)
    {

        // Start a new Requests object, defining a few default request
        // settings
        $session = new Requests_Session($scope->url);
        $session->verify = $scope->validate_cert;
        $session->timeout = $scope->timeout;
        $session->useragent = 'PHPNessusNG/' . $scope->version;

        // Only really needed by $this->token() method. Otherwise we have
        // a cyclic dependency
        if (!$no_token)
            $session->headers['X-Cookie'] = 'token=' . $this->token($scope);

        try {

            // The request itself is aware of the fact that it may have no_token set.
            // For now, if this is true, its assumed its the token() method requesting
            // a call.
            $response = $session
                ->$method(  // The method from via()
                    ($no_token ? 'session/' : $scope->call),
                    array(),
                    ($no_token ? array('username'=>$scope->username, 'password'=>$scope->password) : $scope->fields)
                );

        } catch (\Exception $e) {
            throw new Exception\FailedNessusRequest($e);
        }

        // If a endpoint is called that does not exist, give a slightly easier to
        // understand error.
        if ($response->status_code == 404)
            throw new Exception\FailedNessusRequest(
                'Nessus responded with a 404 for ' . $scope->url . $scope->call . ' via ' . $method . '. Check your call.'
            );

        // Check if a non success HTTP code is received
        if (!$response->success)
            throw new Exception\FailedNessusRequest(
                'Unsuccessfull Request to [' . $method . '] ' . $scope->call . ' Raw: ' . $response->raw
            );

        // If the response is requested in raw format, return it.
        if ($scope->raw)
            return $response->body;

        // Check that the response is not empty. Looks like Nessus returns
        // "null" on empty response :s
        if (is_null($response->body) || trim($response->body) == 'null')
            return null;

        // Check that the JSON is valid
        if (!is_object(json_decode($response->body)))
            throw new Exception\FailedNessusRequest('Failed to parse response JSON');

        return json_decode($response->body);
    }
}