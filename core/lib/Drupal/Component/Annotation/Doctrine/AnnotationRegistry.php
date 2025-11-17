<?php
// phpcs:ignoreFile

/**
 * @file
 *
 * This class is a near-copy of Doctrine\Common\Annotations\AnnotationRegistry,
 * which is part of the Doctrine project: <http://www.doctrine-project.org>. It
 * was copied from version 1.14.4.
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

use function array_key_exists;
use function array_merge;
use function class_exists;
use function in_array;
use function is_file;
use function str_replace;
use function stream_resolve_include_path;
use function strpos;

use const DIRECTORY_SEPARATOR;

final class AnnotationRegistry
{
    /**
     * A map of namespaces to use for autoloading purposes based on a PSR-0 convention.
     *
     * Contains the namespace as key and an array of directories as value. If the value is NULL
     * the include path is used for checking for the corresponding file.
     *
     * This autoloading mechanism does not utilize the PHP autoloading but implements autoloading on its own.
     *
     * @var string[][]|string[]|null[]
     */
    private static $autoloadNamespaces = [];

    /**
     * A map of autoloader callables.
     *
     * @var callable[]
     */
    private static $loaders = [];

    /**
     * An array of classes which cannot be found
     *
     * @var null[] indexed by class name
     */
    private static $failedToAutoload = [];

    /**
     * Whenever registerFile() was used. Disables use of standard autoloader.
     *
     * @var bool
     */
    private static $registerFileUsed = false;

    public static function reset(): void
    {
        self::$autoloadNamespaces = [];
        self::$loaders            = [];
        self::$failedToAutoload   = [];
        self::$registerFileUsed   = false;
    }

    /**
     * Registers file.
     */
    public static function registerFile(string $file): void
    {
        self::$registerFileUsed = true;

        require_once $file;
    }

    /**
     * Adds a namespace with one or many directories to look for files or null for the include path.
     *
     * Loading of this namespaces will be done with a PSR-0 namespace loading algorithm.
     *
     * @phpstan-param string|list<string>|null $dirs
     */
    public static function registerAutoloadNamespace(string $namespace, $dirs = null): void
    {
        self::$autoloadNamespaces[$namespace] = $dirs;
    }

    /**
     * Registers multiple namespaces.
     *
     * Loading of this namespaces will be done with a PSR-0 namespace loading algorithm.
     *
     * @param string[][]|string[]|null[] $namespaces indexed by namespace name
     */
    public static function registerAutoloadNamespaces(array $namespaces): void
    {
        self::$autoloadNamespaces = array_merge(self::$autoloadNamespaces, $namespaces);
    }

    /**
     * Registers an autoloading callable for annotations, much like spl_autoload_register().
     *
     * NOTE: These class loaders HAVE to be silent when a class was not found!
     * IMPORTANT: Loaders have to return true if they loaded a class that could contain the searched annotation class.
     */
    public static function registerLoader(callable $callable): void
    {
        // Reset our static cache now that we have a new loader to work with
        self::$failedToAutoload = [];
        self::$loaders[]        = $callable;
    }

    /**
     * Registers an autoloading callable for annotations, if it is not already registered
     */
    public static function registerUniqueLoader(callable $callable): void
    {
        if (in_array($callable, self::$loaders, true)) {
            return;
        }

        self::registerLoader($callable);
    }

    /**
     * Autoload an annotation class silently.
     */
    public static function loadAnnotationClass(string $class): bool
    {
        if (class_exists($class, false)) {
            return true;
        }

        if (array_key_exists($class, self::$failedToAutoload)) {
            return false;
        }

        foreach (self::$autoloadNamespaces as $namespace => $dirs) {
            if (strpos($class, $namespace) !== 0) {
                continue;
            }

            $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

            if ($dirs === null) {
                $path = stream_resolve_include_path($file);
                if ($path) {
                    require $path;

                    return true;
                }
            } else {
                foreach ((array) $dirs as $dir) {
                    if (is_file($dir . DIRECTORY_SEPARATOR . $file)) {
                        require $dir . DIRECTORY_SEPARATOR . $file;

                        return true;
                    }
                }
            }
        }

        foreach (self::$loaders as $loader) {
            if ($loader($class) === true) {
                return true;
            }
        }

        if (
            self::$loaders === [] &&
            self::$autoloadNamespaces === [] &&
            self::$registerFileUsed === false &&
            class_exists($class)
        ) {
            return true;
        }

        self::$failedToAutoload[$class] = null;

        return false;
    }
}
