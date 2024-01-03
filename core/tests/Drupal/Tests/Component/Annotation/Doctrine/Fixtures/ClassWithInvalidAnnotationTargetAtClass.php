<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures;

use Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetPropertyMethod;

/**
 * @AnnotationTargetPropertyMethod("Some data")
 */
class ClassWithInvalidAnnotationTargetAtClass
{

    /**
     * @AnnotationTargetPropertyMethod("Bar")
     */
    public $foo;
}
