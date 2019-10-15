<?php
// @codingStandardsIgnoreFile

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures\Annotation;

/** @Annotation */
class AnnotWithDefaultValue
{
    /** @var string */
    public $foo = 'bar';
}
