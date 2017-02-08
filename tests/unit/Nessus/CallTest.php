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
        $this->mockCall = Mockery::mock('\Nessus\Nessus\Call')->makePartial();
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
     * @expectedException \GuzzleHttp\Exception\BadResponseException
     */
    public function testTokenNoTokenBadResponse()
    {

        $response = new stdClass();
        $response->token = 'foobar';

        $mockHttpBadResponseException = \Mockery::mock('\GuzzleHttp\Exception\BadResponseException');

        $this->mockClient->token = null;
        $this->mockCall
            ->shouldReceive('call')->with('post', $this->mockClient, true)->andThrow($mockHttpBadResponseException);

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
            ->shouldReceive('request')->with(Mockery::type('GuzzleHttp\Client'), 'get', $this->mockClient, false);

        $this->mockCall->call('get', $this->mockClient);
    }

    /**
     * Test successful GET request.
     */
    public function testGetRequest()
    {
        $this->mockClient->call = 'foobar/';
        $headers = ['X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json'];
        $options = ['headers' => $headers, 'query' => $this->mockClient->fields];

        /** @var \Mockery\Mock|Psr\Http\Message\ResponseInterface $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200)
            ->shouldReceive('getBody')->withNoArgs()->andReturn(json_encode(['1', '2', '3']));

        /** @var \Mockery\Mock|\GuzzleHttp\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\GuzzleHttp\Client');
        $mockHttpClient
            ->shouldReceive('request')->with('GET', $this->mockClient->call, $options)->andReturn($mockHttpResponse);

        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');

        $this->assertEquals(['1', '2', '3'], $this->mockCall->request($mockHttpClient, 'get', $this->mockClient));
    }

    /**
     * Test successful GET request - empty response.
     */
    public function testGetRequestEmptyResponse()
    {
        $this->mockClient->call = 'foobar/';
        $headers = ['X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json'];
        $options = ['headers' => $headers, 'query' => $this->mockClient->fields];

        /** @var \Mockery\Mock|Psr\Http\Message\ResponseInterface $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200)
            ->shouldReceive('getBody')->withNoArgs()->andReturn('');

        /** @var \Mockery\Mock|\GuzzleHttp\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\GuzzleHttp\Client');
        $mockHttpClient
            ->shouldReceive('request')->with('GET', $this->mockClient->call, $options)->andReturn($mockHttpResponse);

        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');

        $this->assertNull($this->mockCall->request($mockHttpClient, 'get', $this->mockClient));
    }

    /**
     * Test successful POST request.
     */
    public function testPostRequest()
    {
        $this->mockClient->call = 'foobar/';
        $headers = ['X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json'];
        $options = ['headers' => $headers, 'json' => $this->mockClient->fields];

        /** @var \Mockery\Mock|Psr\Http\Message\ResponseInterface $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200)
            ->shouldReceive('getBody')->withNoArgs()->andReturn(json_encode(['1', '2', '3']));

        /** @var \Mockery\Mock|\GuzzleHttp\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\GuzzleHttp\Client');
        $mockHttpClient
            ->shouldReceive('request')->with('POST', $this->mockClient->call, $options)->andReturn($mockHttpResponse);

        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');

        $this->assertEquals(['1', '2', '3'], $this->mockCall->request($mockHttpClient, 'post', $this->mockClient));
    }

    /**
     * Test successful PUT request with an empty response.
     */
    public function testPutRequestEmptyResponse()
    {
        $this->mockClient->call = 'foobar/';
        $headers = ['X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json'];
        $options = ['headers' => $headers, 'json' => $this->mockClient->fields];

        /** @var \Mockery\Mock|Psr\Http\Message\ResponseInterface $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200)
            ->shouldReceive('getBody')->withNoArgs()->andReturn('');

        /** @var \Mockery\Mock|\GuzzleHttp\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\GuzzleHttp\Client');
        $mockHttpClient
            ->shouldReceive('request')->with('PUT', $this->mockClient->call, $options)->andReturn($mockHttpResponse);

        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');

        $this->assertNull($this->mockCall->request($mockHttpClient, 'put', $this->mockClient));
    }

    /**
     * Test successful POST request with an empty response.
     */
    public function testPostRequestEmptyResponse()
    {
        $this->mockClient->call = 'foobar/';
        $headers = ['X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json'];
        $options = ['headers' => $headers, 'json' => $this->mockClient->fields];

        /** @var \Mockery\Mock|Psr\Http\Message\ResponseInterface $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200)
            ->shouldReceive('getBody')->withNoArgs()->andReturn('');

        /** @var \Mockery\Mock|\GuzzleHttp\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\GuzzleHttp\Client');
        $mockHttpClient
            ->shouldReceive('request')->with('POST', $this->mockClient->call, $options)->andReturn($mockHttpResponse);

        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');

        $this->assertNull($this->mockCall->request($mockHttpClient, 'post', $this->mockClient));
    }

    /**
     * Test successful PUT request with an empty response.
     */
    public function testDeleteRequestEmptyResponse()
    {
        $this->mockClient->call = 'foobar/';
        $headers = ['X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json'];
        $options = ['headers' => $headers, 'json' => $this->mockClient->fields];

        /** @var \Mockery\Mock|Psr\Http\Message\ResponseInterface $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200)
            ->shouldReceive('getBody')->withNoArgs()->andReturn('');

        /** @var \Mockery\Mock|\GuzzleHttp\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\GuzzleHttp\Client');
        $mockHttpClient
            ->shouldReceive('request')->with('DELETE', $this->mockClient->call, $options)->andReturn($mockHttpResponse);

        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');

        $this->assertNull($this->mockCall->request($mockHttpClient, 'delete', $this->mockClient));
    }

    /**
     * Test successful session/ request.
     */
    public function testRequestToken()
    {

        $this->mockClient->username = 'foo';
        $this->mockClient->password = 'bar';
        $postBody = ['username' => $this->mockClient->username, 'password' => $this->mockClient->password];
        $headers = ['Accept' => 'application/json'];
        $options = ['headers' => $headers, 'json' => $postBody];

        /** @var \Mockery\Mock|Psr\Http\Message\ResponseInterface $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200)
            ->shouldReceive('getBody')->withNoArgs()->andReturn(json_encode(['1', '2', '3']));

        /** @var \Mockery\Mock|\GuzzleHttp\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\GuzzleHttp\Client');
        $mockHttpClient
            ->shouldReceive('request')->with('POST', 'session/', $options)->andReturn($mockHttpResponse);

        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');

        $this->assertEquals(['1', '2', '3'], $this->mockCall->request($mockHttpClient, 'post', $this->mockClient, true));
    }

    /**
     * Test failed request - bad response.
     *
     * @expectedException \Nessus\Exception\FailedNessusRequest
     */
    public function testRequestBadResponse()
    {

        $mockPsrHttpResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $mockPsrHttpResponse->shouldReceive('getStatusCode')->withNoArgs()->andReturn(403);

        $mockHttpBadResponseException = Mockery::mock('\GuzzleHttp\Exception\BadResponseException');
        $mockHttpBadResponseException
            ->shouldReceive('getRequest')->withNoArgs()->andReturn(Mockery::mock('Psr\Http\Message\RequestInterface'))
            ->shouldReceive('getResponse')->withNoArgs()->andReturn($mockPsrHttpResponse);

        $this->mockClient->call = 'foobar/';
        $headers = ['X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json'];
        $options = ['headers' => $headers, 'query' => $this->mockClient->fields];

        /** @var \Mockery\Mock|Psr\Http\Message\ResponseInterface $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200);

        /** @var \Mockery\Mock|\GuzzleHttp\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\GuzzleHttp\Client');
        $mockHttpClient
            ->shouldReceive('request')->with('GET', $this->mockClient->call, $options)->andThrow($mockHttpBadResponseException);

        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');

        $this->assertEquals(['1', '2', '3'], $this->mockCall->request($mockHttpClient, 'get', $this->mockClient));
    }

    /**
     * Test failed request - 404 not found.
     *
     * @expectedException \Nessus\Exception\FailedNessusRequest
     */
    public function testRequestNotFound()
    {

        $this->mockClient->call = 'foobar/';
        $headers = ['X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json'];
        $options = ['headers' => $headers, 'query' => $this->mockClient->fields];

        $mockPsrHttpResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $mockPsrHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(404);

        $mockHttpBadResponseException = Mockery::mock('\GuzzleHttp\Exception\ClientException');
        $mockHttpBadResponseException
            ->shouldReceive('getRequest')->withNoArgs()->andReturn(Mockery::mock('Psr\Http\Message\RequestInterface'))
            ->shouldReceive('getResponse')->withNoArgs()->andReturn($mockPsrHttpResponse);

        /** @var \Mockery\Mock|\GuzzleHttp\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\GuzzleHttp\Client');
        $mockHttpClient
            ->shouldReceive('request')->with('GET', $this->mockClient->call, $options)->andThrow($mockHttpBadResponseException);

        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');

        $this->assertEquals(['1', '2', '3'], $this->mockCall->request($mockHttpClient, 'get', $this->mockClient));
    }

    /**
     * Test failed request - client exception.
     *
     * @expectedException \Nessus\Exception\FailedNessusRequest
     */
    public function testRequestClientException()
    {

        $this->mockClient->call = 'foobar/';
        $headers = ['X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json'];
        $options = ['headers' => $headers, 'query' => $this->mockClient->fields];

        $mockPsrHttpResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $mockPsrHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(400);

        $mockHttpBadResponseException = Mockery::mock('\GuzzleHttp\Exception\ClientException');
        $mockHttpBadResponseException
            ->shouldReceive('getRequest')->withNoArgs()->andReturn(Mockery::mock('Psr\Http\Message\RequestInterface'))
            ->shouldReceive('getResponse')->withNoArgs()->andReturn($mockPsrHttpResponse);

        /** @var \Mockery\Mock|\GuzzleHttp\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\GuzzleHttp\Client');
        $mockHttpClient
            ->shouldReceive('request')->with('GET', $this->mockClient->call, $options)->andThrow($mockHttpBadResponseException);

        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');

        $this->assertEquals(['1', '2', '3'], $this->mockCall->request($mockHttpClient, 'get', $this->mockClient));
    }

    /**
     * Test failed request - server exception.
     *
     * @expectedException \Nessus\Exception\FailedNessusRequest
     */
    public function testRequestServerException()
    {

        $this->mockClient->call = 'foobar/';
        $headers = ['X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json'];
        $options = ['headers' => $headers, 'query' => $this->mockClient->fields];

        $mockPsrHttpResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $mockPsrHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(400);

        $mockHttpBadResponseException = Mockery::mock('\GuzzleHttp\Exception\ServerException');
        $mockHttpBadResponseException
            ->shouldReceive('getRequest')->withNoArgs()->andReturn(Mockery::mock('Psr\Http\Message\RequestInterface'))
            ->shouldReceive('getResponse')->withNoArgs()->andReturn($mockPsrHttpResponse);

        /** @var \Mockery\Mock|\GuzzleHttp\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\GuzzleHttp\Client');
        $mockHttpClient
            ->shouldReceive('request')->with('GET', $this->mockClient->call, $options)->andThrow($mockHttpBadResponseException);

        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');

        $this->assertEquals(['1', '2', '3'], $this->mockCall->request($mockHttpClient, 'get', $this->mockClient));
    }

    /**
     * Test failed request - JSON parse error (syntax error).
     *
     * @expectedException \InvalidArgumentException
     */
    public function testRequestFailedJsonParse()
    {
        $this->mockClient->call = 'foobar/';
        $headers = ['X-Cookie' => 'token=X-Cookie-Token', 'Accept' => 'application/json'];
        $options = ['headers' => $headers, 'query' => $this->mockClient->fields];

        /** @var \Mockery\Mock|Psr\Http\Message\ResponseInterface $mockHttpResponse */
        $mockHttpResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $mockHttpResponse
            ->shouldReceive('getStatusCode')->withNoArgs()->andReturn(200)
            ->shouldReceive('getBody')->withNoArgs()->andReturn('}INVALID JSON{');

        /** @var \Mockery\Mock|\GuzzleHttp\Client $mockHttpClient */
        $mockHttpClient = Mockery::mock('\GuzzleHttp\Client');
        $mockHttpClient
            ->shouldReceive('request')->with('GET', $this->mockClient->call, $options)->andReturn($mockHttpResponse);

        $this->mockCall
            ->shouldReceive('token')->with($this->mockClient)->andReturn('X-Cookie-Token');

        $this->mockCall->request($mockHttpClient, 'get', $this->mockClient);
    }
}