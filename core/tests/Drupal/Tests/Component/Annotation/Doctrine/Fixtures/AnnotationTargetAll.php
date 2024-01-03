<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures;

/**
 * @Annotation
 * @Target("ALL")
 */
class AnnotationTargetAll
{
    public $data;
    public $name;
    public $target;
}
