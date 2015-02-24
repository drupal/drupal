<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Session;
use Symfony\Component\HttpKernel\Client;

/**
 * @group functional
 */
class BaseUrlTest extends \PHPUnit_Framework_TestCase
{
    public function testBaseUrl()
    {
        $client = new Client(require(__DIR__.'/../app.php'));
        $driver = new BrowserKitDriver($client, 'http://localhost/foo/');
        $session = new Session($driver);

        $session->visit('http://localhost/foo/index.html');
        $this->assertEquals(200, $session->getStatusCode());
        $this->assertEquals('http://localhost/foo/index.html', $session->getCurrentUrl());
    }
}
