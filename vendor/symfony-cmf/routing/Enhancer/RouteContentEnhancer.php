<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing\Enhancer;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * This enhancer sets the content to target field if the route provides content.
 *
 * Only works with RouteObjectInterface routes that can return a referenced
 * content.
 *
 * @author David Buchmann
 */
class RouteContentEnhancer implements RouteEnhancerInterface
{
    /**
     * @var string field for the route class
     */
    protected $routefield;
    /**
     * @var string field to write hashmap lookup result into
     */
    protected $target;

    /**
     * @param string $routefield the field name of the route class
     * @param string $target     the field name to set from the map
     * @param array  $hashmap    the map of class names to field values
     */
    public function __construct($routefield, $target)
    {
        $this->routefield = $routefield;
        $this->target = $target;
    }

    /**
     * If the route has a non-null content and if that content class is in the
     * injected map, returns that controller.
     *
     * {@inheritDoc}
     */
    public function enhance(array $defaults, Request $request)
    {
        if (isset($defaults[$this->target])) {
            // no need to do anything
            return $defaults;
        }

        if (! isset($defaults[$this->routefield])
            || ! $defaults[$this->routefield] instanceof RouteObjectInterface
        ) {
            // we can't determine the content
            return $defaults;
        }
        /** @var $route RouteObjectInterface */
        $route = $defaults[$this->routefield];

        $content = $route->getContent();
        if (! $content) {
            // we have no content
            return $defaults;
        }
        $defaults[$this->target] = $content;

        return $defaults;
    }
}
