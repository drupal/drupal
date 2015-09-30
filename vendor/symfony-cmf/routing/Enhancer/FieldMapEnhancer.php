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

/**
 * This enhancer can fill one field with the result of a hashmap lookup of
 * another field. If the target field is already set, it does nothing.
 *
 * @author David Buchmann
 */
class FieldMapEnhancer implements RouteEnhancerInterface
{
    /**
     * @var string field for key in hashmap lookup
     */
    protected $source;
    /**
     * @var string field to write hashmap lookup result into
     */
    protected $target;
    /**
     * @var array containing the mapping between the source field value and target field value
     */
    protected $hashmap;

    /**
     * @param string $source  the field to read
     * @param string $target  the field to write the result of the lookup into
     * @param array  $hashmap for looking up value from source and get value for target
     */
    public function __construct($source, $target, array $hashmap)
    {
        $this->source = $source;
        $this->target = $target;
        $this->hashmap = $hashmap;
    }

    /**
     * If the target field is not set but the source field is, map the field
     *
     * {@inheritDoc}
     */
    public function enhance(array $defaults, Request $request)
    {
        if (isset($defaults[$this->target])) {
            return $defaults;
        }
        if (! isset($defaults[$this->source])) {
            return $defaults;
        }
        if (! isset($this->hashmap[$defaults[$this->source]])) {
            return $defaults;
        }

        $defaults[$this->target] = $this->hashmap[$defaults[$this->source]];

        return $defaults;
    }
}
