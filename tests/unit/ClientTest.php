<?php

/**
 * Class ClientTest
 *
 * @package PHPNessusNG
 * @author  Peter Scopes <@pdscopes>
 * @license MIT
 * @link    https://leonjza.github.io/
 */
class ClientTest extends TestCase
{
    /**
     * @var \Mockery\Mock|\Nessus\Client
     */
    private $mockClient;

    /**
     * @var \Mockery\Mock|\Nessus\Nessus\Call
     */
    private $mockCall;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

        $this->mockClient = Mockery::mock('\Nessus\Client')->makePartial();
        $this->mockCall   = Mockery::mock('\Nessus\Nessus\Call');
    }


    /**
     * Test the via wrapper for making an API call.
     */
    public function testVia()
    {
        $this->mockClient
            ->shouldReceive('makeApiCall')->with(Mockery::type('Nessus\Nessus\Call'), 'get', true)->andReturn(null);

        $this->assertNull($this->mockClient->via('get', true));
    }

    /**
     * Test receiving a valid response from a Nessus Call.
     */
    public function testMakeApiCall()
    {
        $this->mockClient
            ->shouldReceive('makeNessusCall')->withNoArgs()->andReturn($this->mockCall);
        $this->mockCall
            ->shouldReceive('call')->with('get', $this->mockClient)->andReturn(null);

        $this->assertNull($this->mockClient->makeApiCall($this->mockCall, 'get'));
    }

    /**
     * Test sending a request with a bad method.
     *
     * @expectedException \Nessus\Exception\InvalidMethod
     */
    public function testMakeApiCallInvalidMethod()
    {
        $this->mockClient->via('bad_method');
    }

    /**
     * Test sending a request that returns a BadResponseException.
     *
     * @expectedException \Guzzle\Http\Exception\BadResponseException
     */
    public function testMakeApiCallBadResponse()
    {
        $this->mockCall
            ->shouldReceive('call')->with('get', $this->mockClient)->andThrow('\Guzzle\Http\Exception\BadResponseException');

        $this->mockClient->makeApiCall($this->mockCall, 'get', true);
    }
}