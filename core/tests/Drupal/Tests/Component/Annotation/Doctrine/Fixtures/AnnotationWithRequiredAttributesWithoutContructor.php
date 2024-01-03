<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures;

/**
 * @Annotation
 * @Target("ALL")
 */
final class AnnotationWithRequiredAttributesWithoutContructor
{

    /**
     * @Required
     * @var string
     */
    public $value;

    /**
     * @Required
     * @var Drupal\Tests\Component\Annotation\Doctrine\Fixtures\AnnotationTargetAnnotation
     */
    public $annot;

}
