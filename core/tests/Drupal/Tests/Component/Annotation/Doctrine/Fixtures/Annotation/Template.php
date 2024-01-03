<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures\Annotation;

/** @Annotation */
class Template
{
    private $name;

    public function __construct(array $values)
    {
        $this->name = isset($values['value']) ? $values['value'] : null;
    }
}
