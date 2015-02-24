<?php

/*
 * This file is part of the Behat\Mink.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Driver;

use Behat\Mink\Driver\Goutte\Client as ExtendedClient;
use Goutte\Client;

/**
 * Goutte driver.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class GoutteDriver extends BrowserKitDriver
{
    /**
     * Initializes Goutte driver.
     *
     * @param Client $client Goutte client instance
     */
    public function __construct(Client $client = null)
    {
        parent::__construct($client ?: new ExtendedClient());
    }

    /**
     * {@inheritdoc}
     */
    public function setBasicAuth($user, $password)
    {
        if (false === $user) {
            $this->getClient()->resetAuth();

            return;
        }

        $this->getClient()->setAuth($user, $password);
    }

    /**
     * Gets the Goutte client.
     *
     * The method is overwritten only to provide the appropriate return type hint.
     *
     * @return Client
     */
    public function getClient()
    {
        return parent::getClient();
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        parent::reset();
        $this->getClient()->resetAuth();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareUrl($url)
    {
        return $url;
    }
}
