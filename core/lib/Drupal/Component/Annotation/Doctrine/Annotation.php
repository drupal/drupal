<?php
// phpcs:ignoreFile

/**
 * @file
 *
 * This class is a near-copy of Doctrine\Common\Annotations\Annotation, which is
 * part of the Doctrine project: <http://www.doctrine-project.org>. It was
 * copied from version 2.0.2.
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

namespace Drupal\Component\Annotation\Doctrine;

use BadMethodCallException;

use function sprintf;

/**
 * Annotations class.
 */
class Annotation
{
    /**
     * Value property. Common among all derived classes.
     *
     * @var mixed
     */
    public $value;

    /** @param array<string, mixed> $data Key-value for properties to be defined in this class. */
    final public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Error handler for unknown property accessor in Annotation class.
     *
     * @throws BadMethodCallException
     */
    public function __get(string $name): mixed
    {
        throw new BadMethodCallException(
            sprintf("Unknown property '%s' on annotation '%s'.", $name, static::class)
        );
    }

    /**
     * Error handler for unknown property mutator in Annotation class.
     *
     * @param mixed $value Property value.
     *
     * @throws BadMethodCallException
     */
    public function __set(string $name, $value)
    {
        throw new BadMethodCallException(
            sprintf("Unknown property '%s' on annotation '%s'.", $name, static::class)
        );
    }
}
