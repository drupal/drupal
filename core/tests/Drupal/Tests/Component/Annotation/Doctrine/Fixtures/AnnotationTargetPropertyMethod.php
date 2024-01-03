<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures;

/**
 * @Annotation
 * @Target({ "METHOD", "PROPERTY" })
 */
final class AnnotationTargetPropertyMethod
{
    public $data;
    public $name;
    public $target;
}
