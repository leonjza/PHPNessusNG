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

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Nessus\Exception;

/**
 * Class Call
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
     * @param  string $method   The method that should be used in the HTTP request
     * @param  object $scope    The scope injected from a \Nessus\Client
     * @param  bool   $no_token Should a token be used in this request
     *
     * @return null|object[]|object|string
     */
    public function call($method, $scope, $no_token = false)
    {

        // Prepare the configuration for a new Guzzle client
        $config = [
            'base_uri' => $scope->url,
            'verify'   => $scope->validate_cert,
            'timeout'  => $scope->timeout,
            'headers'  => [
                'User-Agent' => 'PHPNessusNG/' . $scope->version
            ]
        ];

        // Detect if we have a proxy configured
        if ($scope->use_proxy) {

            // If we have a username or password, add it to the proxy
            // setting
            if (!is_null($scope->proxy_user) || !is_null($scope->proxy_pass)) {
                $config['proxy'] = 'tcp://' . $scope->proxy_user . ':' . $scope->proxy_pass .
                                   '@' . $scope->proxy_host . ':' . $scope->proxy_port;
            } else {
                $config['proxy'] = 'tcp://' . $scope->proxy_host . ':' . $scope->proxy_port;
            }
        }

        $client = new HttpClient($config);

        return $this->request($client, $method, $scope, $no_token);
    }

    /**
     * Makes an API call to a Nessus Scanner
     *
     * @param HttpClient $client   The HttpClient that should be used to make the request
     * @param string     $method   The method that should be used in the HTTP request
     * @param object     $scope    The scope injected from a \Nessus\Client
     * @param bool       $no_token Should a token be used in this request
     *
     * @return null|object[]|object|string
     *
     * @throws Exception\FailedNessusRequest
     * @throws Exception\FailedConnection
     * @throws \InvalidArgumentException
     */
    public function request(HttpClient $client, $method, $scope, $no_token = false)
    {
        // Define the uri and default options
        $method  = strtoupper($method);
        $uri     = $scope->call;
        $options = ['headers' => ['Accept' => 'application/json']];

        // If we have $no_token set, we assume that this is the login request
        // that we have received. So, we will override the body with the
        // username and password
        if (!$no_token) {
            // Only really needed by $this->token() method. Otherwise we have
            // a cyclic dependency trying to setup a token
            $options['headers']['X-Cookie'] = 'token=' . $this->token($scope);

            // Methods such as PUT, DELETE and POST require us to set a body. We will
            // json encode this and set it
            if (in_array($method, ['PUT', 'POST', 'DELETE'])) {
                $options['json'] = $scope->fields;
            } else {
                $options['query'] = $scope->fields;
            }
        } else {
            // $no_token may mean a token request
            $uri             = 'session/';
            $options['json'] = [
                'username' => $scope->username,
                'password' => $scope->password
            ];
        }

        // Attempt the actual response that has been built thus far
        try {
            $response = $client->request($method, $uri, $options);
        }
        catch (ClientException $clientException) {
            // If a endpoint is called that does not exist, give a slightly easier to
            // understand error.
            if ($clientException->getResponse()->getStatusCode() == 404) {
                throw Exception\FailedNessusRequest::exceptionFactory(
                    'Nessus responded with a 404 for ' . $scope->url . $scope->call . ' via ' . $method . '. Check your call.',
                    $clientException->getRequest(),
                    $clientException->getResponse()
                );
            }

            throw Exception\FailedNessusRequest::exceptionFactory(
                $clientException->getMessage(), $clientException->getRequest(), $clientException->getResponse()
            );
        }
        catch (ServerException $serverException) {
            throw Exception\FailedNessusRequest::exceptionFactory(
                $serverException->getMessage(), $serverException->getRequest(), $serverException->getResponse()
            );
        }
        catch (BadResponseException $badResponseException) {
            throw Exception\FailedNessusRequest::exceptionFactory(
                'Unsuccessful Request to [' . $method . '] ' . $scope->call,
                $badResponseException->getRequest(),
                $badResponseException->getResponse()
            );
        }
        catch (ConnectException $connectException) {
            throw new Exception\FailedConnection($connectException->getMessage(), $connectException->getCode());
        }

        // If the response is requested in raw format, return it. We need
        // to be careful to not return raw to a token request too.
        if ($scope->raw && !$no_token) {
            return (string) $response->getBody();
        }

        // Check that the response is not empty. Looks like Nessus returns
        // "null" on empty response :s
        if (is_null($response->getBody()) || trim($response->getBody()) == 'null') {
            return null;
        }


        // We assume that Nessus can return empty bodies and that Nessus will
        // use HTTP status codes to inform us whether the request failed.
        if (trim($response->getBody()) == '') {
            return null;
        }

        // Attempt to convert the response to a JSON Object
        return \GuzzleHttp\json_decode($response->getBody());
    }
}
