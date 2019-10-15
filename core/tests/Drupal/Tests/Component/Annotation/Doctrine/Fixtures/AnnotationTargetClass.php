<?php
// @codingStandardsIgnoreFile

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures;


/**
 * @Annotation
 * @Target("CLASS")
 */
final class AnnotationTargetClass
{
    public $data;
    public $name;
    public $target;
}
