<?php

namespace Behat\Mink\Tests\Driver;

use Behat\Mink\Driver\BrowserKitDriver;
use Symfony\Component\HttpKernel\Client;

class BrowserKitConfig extends AbstractConfig
{
    public static function getInstance()
    {
        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver()
    {
        $client = new Client(require(__DIR__.'/app.php'));

        return new BrowserKitDriver($client);
    }

    /**
     * {@inheritdoc}
     */
    public function getWebFixturesUrl()
    {
        return 'http://localhost';
    }

    protected function supportsJs()
    {
        return false;
    }
}
