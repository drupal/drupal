<?php
// phpcs:ignoreFile

/**
 * @file
 *
 * This class is a near-copy of
 * Doctrine\Common\Annotations\Annotation\Enum, which is part of the Doctrine
 * project: <http://www.doctrine-project.org>. It was copied from version 2.0.2.
 *
 * Original copyright:
 *
 * Copyright (c) 2006-2013 Doctrine Project
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */

namespace Drupal\Component\Annotation\Doctrine\Annotation;

use InvalidArgumentException;

use function get_class;
use function gettype;
use function in_array;
use function is_object;
use function is_scalar;
use function sprintf;

/**
 * Annotation that can be used to signal to the parser
 * to check the available values during the parsing process.
 *
 * @Annotation
 * @Attributes({
 *    @Attribute("value",   required = true,  type = "array"),
 *    @Attribute("literal", required = false, type = "array")
 * })
 */
final class Enum
{
    /** @phpstan-var list<scalar> */
    public $value;

    /**
     * Literal target declaration.
     *
     * @var mixed[]
     */
    public $literal;

    /**
     * @phpstan-param array{literal?: mixed[], value: list<scalar>} $values
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $values)
    {
        if (! isset($values['literal'])) {
            $values['literal'] = [];
        }

        foreach ($values['value'] as $var) {
            if (! is_scalar($var)) {
                throw new InvalidArgumentException(sprintf(
                    '@Enum supports only scalar values "%s" given.',
                    is_object($var) ? get_class($var) : gettype($var)
                ));
            }
        }

        foreach ($values['literal'] as $key => $var) {
            if (! in_array($key, $values['value'])) {
                throw new InvalidArgumentException(sprintf(
                    'Undefined enumerator value "%s" for literal "%s".',
                    $key,
                    $var
                ));
            }
        }

        $this->value   = $values['value'];
        $this->literal = $values['literal'];
    }
}
