<?php

namespace Behat\Mink\Tests\Driver;

use Behat\Mink\Driver\GoutteDriver;

class GoutteConfig extends AbstractConfig
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
        return new GoutteDriver();
    }

    protected function supportsJs()
    {
        return false;
    }
}
