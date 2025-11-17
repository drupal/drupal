<?php
// phpcs:ignoreFile

/**
 * @file
 *
 * This class is a near-copy of
 * Doctrine\Common\Annotations\Annotation\Target, which is part of the Doctrine
 * project: <http://www.doctrine-project.org>. It was copied from version 1.14.4.
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

use function array_keys;
use function get_class;
use function gettype;
use function implode;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Annotation that can be used to signal to the parser
 * to check the annotation target during the parsing process.
 *
 * @Annotation
 */
final class Target
{
    public const TARGET_CLASS      = 1;
    public const TARGET_METHOD     = 2;
    public const TARGET_PROPERTY   = 4;
    public const TARGET_ANNOTATION = 8;
    public const TARGET_FUNCTION   = 16;
    public const TARGET_ALL        = 31;

    /** @var array<string, int> */
    private static $map = [
        'ALL'        => self::TARGET_ALL,
        'CLASS'      => self::TARGET_CLASS,
        'METHOD'     => self::TARGET_METHOD,
        'PROPERTY'   => self::TARGET_PROPERTY,
        'FUNCTION'   => self::TARGET_FUNCTION,
        'ANNOTATION' => self::TARGET_ANNOTATION,
    ];

    /** @phpstan-var list<string> */
    public $value;

    /**
     * Targets as bitmask.
     *
     * @var int
     */
    public $targets;

    /**
     * Literal target declaration.
     *
     * @var string
     */
    public $literal;

    /**
     * @phpstan-param array{value?: string|list<string>} $values
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $values)
    {
        if (! isset($values['value'])) {
            $values['value'] = null;
        }

        if (is_string($values['value'])) {
            $values['value'] = [$values['value']];
        }

        if (! is_array($values['value'])) {
            throw new InvalidArgumentException(
                sprintf(
                    '@Target expects either a string value, or an array of strings, "%s" given.',
                    is_object($values['value']) ? get_class($values['value']) : gettype($values['value'])
                )
            );
        }

        $bitmask = 0;
        foreach ($values['value'] as $literal) {
            if (! isset(self::$map[$literal])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid Target "%s". Available targets: [%s]',
                        $literal,
                        implode(', ', array_keys(self::$map))
                    )
                );
            }

            $bitmask |= self::$map[$literal];
        }

        $this->targets = $bitmask;
        $this->value   = $values['value'];
        $this->literal = implode(', ', $this->value);
    }
}
