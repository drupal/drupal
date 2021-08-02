<?php
// phpcs:ignoreFile

/**
 * @file
 *
 * This class is a near-copy of
 * Doctrine\Common\Reflection\Compatibility\ReflectionClass, which is part of
 * the Doctrine project: <http://www.doctrine-project.org>. It was copied from
 * version 1.2.2.
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

namespace Drupal\Component\Annotation\Doctrine\Compatibility;

use const PHP_VERSION_ID;
use function class_alias;

if (PHP_VERSION_ID >= 80000) {
    class_alias('Drupal\Component\Annotation\Doctrine\Compatibility\Php8\ReflectionClass', 'Drupal\Component\Annotation\Doctrine\Compatibility\ReflectionClass');
} else {
    class_alias('Drupal\Component\Annotation\Doctrine\Compatibility\Php7\ReflectionClass', 'Drupal\Component\Annotation\Doctrine\Compatibility\ReflectionClass');
}

if (false) {
    class ReflectionClass
    {
    }
}
