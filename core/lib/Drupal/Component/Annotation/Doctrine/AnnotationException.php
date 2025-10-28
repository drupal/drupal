<?php
// phpcs:ignoreFile
// cspell:ignore optimizerplus

/**
 * @file
 *
 * This class is a near-copy of Doctrine\Common\Annotations\AnnotationException,
 * which is part of the Doctrine project: <http://www.doctrine-project.org>. It
 * was copied from version 2.0.2.
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

use Exception;
use Throwable;

use function get_class;
use function gettype;
use function implode;
use function is_object;
use function sprintf;

/**
 * Description of AnnotationException
 */
class AnnotationException extends Exception
{
    /**
     * Creates a new AnnotationException describing a Syntax error.
     *
     * @return AnnotationException
     */
    public static function syntaxError(string $message)
    {
        return new self('[Syntax Error] ' . $message);
    }

    /**
     * Creates a new AnnotationException describing a Semantical error.
     *
     * @return AnnotationException
     */
    public static function semanticalError(string $message)
    {
        return new self('[Semantical Error] ' . $message);
    }

    /**
     * Creates a new AnnotationException describing an error which occurred during
     * the creation of the annotation.
     *
     * @return AnnotationException
     */
    public static function creationError(string $message, ?Throwable $previous = null)
    {
        return new self('[Creation Error] ' . $message, 0, $previous);
    }

    /**
     * Creates a new AnnotationException describing a type error.
     *
     * @return AnnotationException
     */
    public static function typeError(string $message)
    {
        return new self('[Type Error] ' . $message);
    }

    /**
     * Creates a new AnnotationException describing a constant semantical error.
     *
     * @return AnnotationException
     */
    public static function semanticalErrorConstants(string $identifier, ?string $context = null)
    {
        return self::semanticalError(sprintf(
            "Couldn't find constant %s%s.",
            $identifier,
            $context ? ', ' . $context : ''
        ));
    }

    /**
     * Creates a new AnnotationException describing an type error of an attribute.
     *
     * @param mixed $actual
     *
     * @return AnnotationException
     */
    public static function attributeTypeError(
        string $attributeName,
        string $annotationName,
        string $context,
        string $expected,
        $actual
    ) {
        return self::typeError(sprintf(
            'Attribute "%s" of @%s declared on %s expects %s, but got %s.',
            $attributeName,
            $annotationName,
            $context,
            $expected,
            is_object($actual) ? 'an instance of ' . get_class($actual) : gettype($actual)
        ));
    }

    /**
     * Creates a new AnnotationException describing an required error of an attribute.
     *
     * @return AnnotationException
     */
    public static function requiredError(
        string $attributeName,
        string $annotationName,
        string $context,
        string $expected
    ) {
        return self::typeError(sprintf(
            'Attribute "%s" of @%s declared on %s expects %s. This value should not be null.',
            $attributeName,
            $annotationName,
            $context,
            $expected
        ));
    }

    /**
     * Creates a new AnnotationException describing a invalid enumerator.
     *
     * @param mixed $given
     * @phpstan-param list<string> $available
     *
     * @return AnnotationException
     */
    public static function enumeratorError(
        string $attributeName,
        string $annotationName,
        string $context,
        array $available,
        $given
    ) {
        return new self(sprintf(
            '[Enum Error] Attribute "%s" of @%s declared on %s accepts only [%s], but got %s.',
            $attributeName,
            $annotationName,
            $context,
            implode(', ', $available),
            is_object($given) ? get_class($given) : $given
        ));
    }

    /** @return AnnotationException */
    public static function optimizerPlusSaveComments()
    {
        return new self(
            'You have to enable opcache.save_comments=1 or zend_optimizerplus.save_comments=1.'
        );
    }

    /** @return AnnotationException */
    public static function optimizerPlusLoadComments()
    {
        return new self(
            'You have to enable opcache.load_comments=1 or zend_optimizerplus.load_comments=1.'
        );
    }
}
