<?php
// phpcs:ignoreFile

/**
 * @file
 *
 * This class is a near-copy of
 * Doctrine\Common\Annotations\SimpleAnnotationReader, which is part of the
 * Doctrine project: <http://www.doctrine-project.org>. It was copied from
 * version 1.2.7.
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

use Doctrine\Common\Annotations\Reader;

/**
 * Simple Annotation Reader.
 *
 * Drupal adds its own version of DocParser and allows for ignoring common
 * annotations.
 *
 * @internal
 */
final class SimpleAnnotationReader implements Reader
{

    protected $ignoredAnnotations = [
      'addtogroup' => TRUE,
      'code' => TRUE,
      'defgroup' => TRUE,
      'deprecated' => TRUE,
      'endcode' => TRUE,
      'endlink' => TRUE,
      'file' => TRUE,
      'ingroup' => TRUE,
      'group' => TRUE,
      'link' => TRUE,
      'mainpage' => TRUE,
      'param' => TRUE,
      'ref' => TRUE,
      'return' => TRUE,
      'section' => TRUE,
      'see' => TRUE,
      'subsection' => TRUE,
      'throws' => TRUE,
      'todo' => TRUE,
      'var' => TRUE,
      '{' => TRUE,
      '}' => TRUE,
    ];

    /**
     * @var DocParser
     */
    private $parser;

    /**
     * Constructor.
     *
     * Initializes a new SimpleAnnotationReader.
     */
    public function __construct()
    {
        $this->parser = new DocParser();
        $this->parser->setIgnoreNotImportedAnnotations(true);
        $this->parser->setIgnoredAnnotationNames($this->ignoredAnnotations);
    }

    /**
     * Adds a namespace in which we will look for annotations.
     *
     * @param string $namespace
     *
     * @return void
     */
    public function addNamespace($namespace)
    {
        $this->parser->addNamespace($namespace);
    }

    /**
     * {@inheritDoc}
     */
    public function getClassAnnotations(\ReflectionClass $class)
    {
        return $this->parser->parse($class->getDocComment(), 'class '.$class->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        return $this->parser->parse($method->getDocComment(), 'method '.$method->getDeclaringClass()->name.'::'.$method->getName().'()');
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        return $this->parser->parse($property->getDocComment(), 'property '.$property->getDeclaringClass()->name.'::$'.$property->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        foreach ($this->getClassAnnotations($class) as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        foreach ($this->getMethodAnnotations($method) as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        foreach ($this->getPropertyAnnotations($property) as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }
}
