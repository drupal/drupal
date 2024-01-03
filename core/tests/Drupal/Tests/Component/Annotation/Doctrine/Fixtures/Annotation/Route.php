<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures\Annotation;

/** @Annotation */
class Route
{
    /** @var string @Required */
    public $pattern;
    public $name;
}
