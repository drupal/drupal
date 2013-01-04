<?php

namespace Symfony\Cmf\Component\Routing\Enhancer;

use Symfony\Component\HttpFoundation\Request;

/**
 * This enhancer can set a field to a fixed value if an other field is present.
 *
 * @author David Buchmann
 */
class FieldPresenceEnhancer implements RouteEnhancerInterface
{
    /**
     * @var string field for the source class
     */
    protected $source;
    /**
     * @var string field to write hashmap lookup result into
     */
    protected $target;
    /**
     * value to set the target field to
     *
     * @var string
     */
    private $value;

    /**
     * @param string $source the field name of the class
     * @param string $target the field name to set from the map
     * @param string $value  value to set target field to if source field exists
     */
    public function __construct($source, $target, $value)
    {
        $this->source = $source;
        $this->target = $target;
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function enhance(array $defaults, Request $request)
    {
        if (isset($defaults[$this->target])) {
            // no need to do anything
            return $defaults;
        }

        if (! isset($defaults[$this->source])) {
            return $defaults;
        }

        $defaults[$this->target] = $this->value;

        return $defaults;
    }

}
