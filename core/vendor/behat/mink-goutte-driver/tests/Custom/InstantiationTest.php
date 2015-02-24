<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Driver\GoutteDriver;

class InstantiationTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiateWithClient()
    {
        $client = $this->getMockBuilder('Goutte\Client')->disableOriginalConstructor()->getMock();
        $client->expects($this->once())
            ->method('followRedirects')
            ->with(true);

        $driver = new GoutteDriver($client);

        $this->assertSame($client, $driver->getClient());
    }

    public function testInstantiateWithoutClient()
    {
        $driver = new GoutteDriver();

        $this->assertInstanceOf('Behat\Mink\Driver\Goutte\Client', $driver->getClient());
    }
}
