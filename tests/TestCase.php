<?php

/**
 * Class ClientTest
 *
 * @package PHPNessusNG
 * @author  Peter Scopes <@pdscopes>
 * @license MIT
 * @link    https://leonjza.github.io/
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
    }
}