<?php

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures;

use Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetClass;
use Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAll;
use Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetPropertyMethod;

/**
 * @AnnotationTargetClass("Some data")
 */
class ClassWithValidAnnotationTarget
{

    /**
     * @AnnotationTargetPropertyMethod("Some data")
     */
    public $foo;


    /**
     * @AnnotationTargetAll("Some data",name="Some name")
     */
    public $name;

    /**
     * @AnnotationTargetPropertyMethod("Some data",name="Some name")
     */
    public function someFunction()
    {

    }


    /**
     * @AnnotationTargetAll(@AnnotationTargetAnnotation)
     */
    public $nested;

}
