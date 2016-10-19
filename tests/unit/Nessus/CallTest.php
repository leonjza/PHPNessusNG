<?php

/**
 * Class ClientTest
 *
 * @package PHPNessusNG
 * @author  Peter Scopes <@pdscopes>
 * @license MIT
 * @link    https://leonjza.github.io/
 */
class CallTest extends TestCase
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

        $this->mockClient = Mockery::mock('\Nessus\Client');
        $this->mockCall   = Mockery::mock('\Nessus\Nessus\Call')->makePartial();
    }


    /**
     * Test token retrieval from the Nessus Scanner.
     */
    public function testTokenNoToken()
    {
        $response = new stdClass();
        $response->token = 'foobar';

        $this->mockClient->token = null;
        $this->mockCall
            ->shouldReceive('call')->with('post', $this->mockClient, true)->andReturn($response);

        $token = $this->mockCall->token($this->mockClient);

        $this->assertEquals($response->token, $token);
        $this->assertEquals($response->token, $this->mockClient->token);
    }

    /**
     * Test token retrieval from Nessus Scanner - bad response exception.
     *
     * @expectedException \Guzzle\Http\Exception\BadResponseException
     */
    public function testTokenNoTokenBadResponse()
    {
        $response = new stdClass();
        $response->token = 'foobar';

        $this->mockClient->token = null;
        $this->mockCall
            ->shouldReceive('call')->with('post', $this->mockClient, true)->andThrow('\Guzzle\Http\Exception\BadResponseException');

        $this->mockCall->token($this->mockClient);

    }

    /**
     * Test token retrieval once token has been retrieved already.
     */
    public function testTokenWithToken()
    {
        $this->mockClient->token = 'foobar';
        $this->mockCall
            ->shouldNotReceive('call');

        $token = $this->mockCall->token($this->mockClient);

        $this->assertEquals($this->mockClient->token, $token);
    }

    /**
     * Test call creates a Guzzle Http client and makes a request.
     */
    public function testCall()
    {
        $this->mockCall
            ->shouldReceive('request')->with(Mockery::type('Guzzle\Http\Client'), 'get', $this->mockClient, false);

        $this->mockCall->call('get', $this->mockClient);
    }

    /**
     * Test successful GET request.
     */
    public function testGetRequest()
    {
        $this->mockClient->call = 'foobar/';
        $headers = array('X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json');

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Response $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('\Guzzle\Http\Message\Response');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200)
            ->shouldReceive('isSuccessful')->withNoArgs()->andReturn(true)
            ->shouldReceive('getBody')->withNoArgs()->andReturn(json_encode(array('1','2','3')));

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Request $mockHttpRequest */
        $mockHttpRequest = Mockery::mock('\Guzzle\Http\Message\Request');
        $mockHttpRequest
            ->shouldReceive('send')->withNoArgs()->andReturn($mockHttpResponse);

        /** @var \Mockery\Mock|\Guzzle\Http\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\Guzzle\Http\Client');
        $mockHttpClient
            ->shouldReceive('get')->with($this->mockClient->call, $headers,  $this->mockClient->fields)->andReturn($mockHttpRequest);


        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');


        $this->assertEquals(array('1','2','3'), $this->mockCall->request($mockHttpClient, 'get', $this->mockClient));
    }

    /**
     * Test successful POST request.
     */
    public function testPostRequest()
    {
        $this->mockClient->call = 'foobar/';
        $headers = array('X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json');

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Response $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('\Guzzle\Http\Message\Response');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200)
            ->shouldReceive('isSuccessful')->withNoArgs()->andReturn(true)
            ->shouldReceive('getBody')->withNoArgs()->andReturn(json_encode(array('1','2','3')));

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Request $mockHttpRequest */
        $mockHttpRequest = Mockery::mock('\Guzzle\Http\Message\Request');
        $mockHttpRequest
            ->shouldReceive('setBody')->with(json_encode($this->mockClient->fields), 'application/json')
            ->shouldReceive('send')->withNoArgs()->andReturn($mockHttpResponse);

        /** @var \Mockery\Mock|\Guzzle\Http\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\Guzzle\Http\Client');
        $mockHttpClient
            ->shouldReceive('post')->with($this->mockClient->call, $headers)->andReturn($mockHttpRequest);


        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');


        $this->assertEquals(array('1','2','3'), $this->mockCall->request($mockHttpClient, 'post', $this->mockClient));
    }

    /**
     * Test successful session/ request.
     */
    public function testRequestToken()
    {
        $this->mockClient->username = 'foo';
        $this->mockClient->password = 'bar';
        $postBody = array('username' => $this->mockClient->username, 'password' => $this->mockClient->password);
        $headers  = array('Accept' => 'application/json');

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Response $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('\Guzzle\Http\Message\Response');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200)
            ->shouldReceive('isSuccessful')->withNoArgs()->andReturn(true)
            ->shouldReceive('getBody')->withNoArgs()->andReturn(json_encode(array('1','2','3')));

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Request $mockHttpRequest */
        $mockHttpRequest = Mockery::mock('\Guzzle\Http\Message\Request');
        $mockHttpRequest
            ->shouldReceive('setBody')->with(json_encode($postBody), 'application/json')
            ->shouldReceive('send')->withNoArgs()->andReturn($mockHttpResponse);

        /** @var \Mockery\Mock|\Guzzle\Http\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\Guzzle\Http\Client');
        $mockHttpClient
            ->shouldReceive('post')->with('session/', $headers)->andReturn($mockHttpRequest);


        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');


        $this->assertEquals(array('1','2','3'), $this->mockCall->request($mockHttpClient, 'post', $this->mockClient, true));
    }

    /**
     * Test failed request - bad response.
     *
     * @expectedException \Nessus\Exception\FailedNessusRequest
     */
    public function testRequestBadResponse()
    {
        $mockHttpBadResponseException = Mockery::mock('\Guzzle\Http\Exception\BadResponseException');
        $mockHttpBadResponseException
            ->shouldReceive('getRequest')->withNoArgs()->andReturn(Mockery::mock('\Guzzle\Http\Message\Request'))
            ->shouldReceive('getResponse')->withNoArgs()->andReturn(Mockery::mock('\Guzzle\Http\Message\Response'));

        $this->mockClient->call = 'foobar/';
        $headers = array('X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json');

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Response $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('\Guzzle\Http\Message\Response');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200);

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Request $mockHttpRequest */
        $mockHttpRequest = Mockery::mock('\Guzzle\Http\Message\Request');
        $mockHttpRequest
            ->shouldReceive('send')->withNoArgs()->andThrow($mockHttpBadResponseException);

        /** @var \Mockery\Mock|\Guzzle\Http\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\Guzzle\Http\Client');
        $mockHttpClient
            ->shouldReceive('get')->with($this->mockClient->call, $headers,  $this->mockClient->fields)->andReturn($mockHttpRequest);


        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');


        $this->assertEquals(array('1','2','3'), $this->mockCall->request($mockHttpClient, 'get', $this->mockClient));
    }

    /**
     * Test failed request - 404 not found.
     *
     * @expectedException \Nessus\Exception\FailedNessusRequest
     */
    public function testRequestNotFound()
    {
        $this->mockClient->call = 'foobar/';
        $headers = array('X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json');

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Response $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('\Guzzle\Http\Message\Response');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(404);

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Request $mockHttpRequest */
        $mockHttpRequest = Mockery::mock('\Guzzle\Http\Message\Request');
        $mockHttpRequest
            ->shouldReceive('send')->withNoArgs()->andReturn($mockHttpResponse);

        /** @var \Mockery\Mock|\Guzzle\Http\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\Guzzle\Http\Client');
        $mockHttpClient
            ->shouldReceive('get')->with($this->mockClient->call, $headers,  $this->mockClient->fields)->andReturn($mockHttpRequest);


        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');


        $this->assertEquals(array('1','2','3'), $this->mockCall->request($mockHttpClient, 'get', $this->mockClient));
    }

    /**
     * Test failed request - response unsuccessful.
     *
     * @expectedException \Nessus\Exception\FailedNessusRequest
     */
    public function testRequestUnsuccessfulResponse()
    {
        $this->mockClient->call = 'foobar/';
        $headers = array('X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json');

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Response $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('\Guzzle\Http\Message\Response');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200)
            ->shouldReceive('isSuccessful')->withNoArgs()->andReturn(false);

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Request $mockHttpRequest */
        $mockHttpRequest = Mockery::mock('\Guzzle\Http\Message\Request');
        $mockHttpRequest
            ->shouldReceive('send')->withNoArgs()->andReturn($mockHttpResponse);

        /** @var \Mockery\Mock|\Guzzle\Http\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\Guzzle\Http\Client');
        $mockHttpClient
            ->shouldReceive('get')->with($this->mockClient->call, $headers,  $this->mockClient->fields)->andReturn($mockHttpRequest);


        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');


        $this->assertEquals(array('1','2','3'), $this->mockCall->request($mockHttpClient, 'get', $this->mockClient));
    }

    /**
     * Test failed request - JSON parse error.
     *
     * @expectedException \Nessus\Exception\FailedNessusRequest
     */
    public function testRequestFailedJsonParse()
    {
        $this->mockClient->call = 'foobar/';
        $headers = array('X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json');

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Response $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('\Guzzle\Http\Message\Response');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200)
            ->shouldReceive('isSuccessful')->withNoArgs()->andReturn(true)
            ->shouldReceive('getBody')->withNoArgs()->andReturn('}INVALID JSON{');

        /** @var \Mockery\Mock|\Guzzle\Http\Message\Request $mockHttpRequest */
        $mockHttpRequest = Mockery::mock('\Guzzle\Http\Message\Request');
        $mockHttpRequest
            ->shouldReceive('send')->withNoArgs()->andReturn($mockHttpResponse);

        /** @var \Mockery\Mock|\Guzzle\Http\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\Guzzle\Http\Client');
        $mockHttpClient
            ->shouldReceive('get')->with($this->mockClient->call, $headers,  $this->mockClient->fields)->andReturn($mockHttpRequest);


        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');


        $this->assertEquals(array('1','2','3'), $this->mockCall->request($mockHttpClient, 'get', $this->mockClient));
    }
}