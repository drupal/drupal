<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing\Tests\Routing;

use Symfony\Component\Routing\Route as SymfonyRoute;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;

class RouteMock extends SymfonyRoute implements RouteObjectInterface
{
    private $locale;

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getContent()
    {
        return null;
    }

    public function getDefaults()
    {
        $defaults = array();
        if (! is_null($this->locale)) {
            $defaults['_locale'] = $this->locale;
        }

        return $defaults;
    }

    public function getRequirement($key)
    {
        if (! $key == '_locale') {
            throw new \Exception;
        }

        return $this->locale;
    }

    public function getRouteKey()
    {
        return null;
    }
}
