<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures;

/**
 * @Annotation
 * @Target({ "ANNOTATION" })
 */
final class AnnotationTargetAnnotation
{
    public $data;
    public $name;
    public $target;
}
