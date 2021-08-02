<?php
// phpcs:ignoreFile

/**
 * @file
 *
 * This class is a near-copy of
 * Doctrine\Common\Reflection\Compatibility\Php7\ReflectionClass, which is part
 * of the Doctrine project: <http://www.doctrine-project.org>. It was copied
 * from version 1.2.2.
 *
 * Original copyright:
 *
 * Copyright (c) 2006-2015 Doctrine Project
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

namespace Drupal\Component\Annotation\Doctrine\Compatibility\Php7;

use ReflectionException;

trait ReflectionClass
{
    /**
     * {@inheritDoc}
     */
    public function getConstants()
    {
        throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function newInstance($args)
    {
        throw new ReflectionException('Method not implemented');
    }
}
