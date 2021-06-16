<?php

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures;

use Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetClass;
use Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAnnotation;

/**
 * @AnnotationTargetClass("Some data")
 */
class ClassWithInvalidAnnotationTargetAtProperty
{

    /**
     * @AnnotationTargetClass("Bar")
     */
    public $foo;


    /**
     * @AnnotationTargetAnnotation("Foo")
     */
    public $bar;
}
