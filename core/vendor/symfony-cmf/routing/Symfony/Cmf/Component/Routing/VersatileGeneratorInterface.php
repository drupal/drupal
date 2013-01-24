<?php

namespace Symfony\Cmf\Component\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This generator is able to handle more than string route names as symfony
 * core supports them.
 */
interface VersatileGeneratorInterface extends UrlGeneratorInterface
{
    /**
     * If $name preg_match this pattern, the name is valid for symfony core
     * compatible generators.
     */
    const CORE_NAME_PATTERN = '/^[a-z0-9A-Z_.]+$/';

    /**
     * Whether this generator supports the supplied $name.
     *
     * This check does not need to look if the specific instance can be
     * resolved to a route, only whether the router can generate routes from
     * objects of this class.
     *
     * @param mixed $name The route "name" which may also be an object or anything
     *
     * @return bool
     */
    public function supports($name);
}

