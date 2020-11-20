<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2015 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing\Enhancer;

use Symfony\Component\HttpFoundation\Request;

/**
 * This enhancer sets a field to a fixed value. You can specify a source field
 * name to only set the target field if the source is present.
 *
 * @author David Buchmann
 */
class FieldPresenceEnhancer implements RouteEnhancerInterface
{
    /**
     * Field name for the source field that must exist. If null, the target
     * field is always set if not already present.
     *
     * @var string|null
     */
    protected $source;

    /**
     * Field name to write the value into.
     *
     * @var string
     */
    protected $target;

    /**
     * Value to set the target field to.
     *
     * @var string
     */
    private $value;

    /**
     * @param null|string $source the field name of the class, null to disable the check
     * @param string      $target the field name to set from the map
     * @param string      $value  value to set target field to if source field exists
     */
    public function __construct($source, $target, $value)
    {
        $this->source = $source;
        $this->target = $target;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function enhance(array $defaults, Request $request)
    {
        if (isset($defaults[$this->target])) {
            // no need to do anything
            return $defaults;
        }

        if (null !== $this->source && !isset($defaults[$this->source])) {
            return $defaults;
        }

        $defaults[$this->target] = $this->value;

        return $defaults;
    }
}
