<?php
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
    public function getRouteContent()
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
