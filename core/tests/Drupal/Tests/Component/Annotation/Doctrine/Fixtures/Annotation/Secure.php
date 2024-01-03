<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation\Doctrine\Fixtures\Annotation;

/** @Annotation */
class Secure
{
    private $roles;

    public function __construct(array $values)
    {
        if (is_string($values['value'])) {
            $values['value'] = array($values['value']);
        }

        $this->roles = $values['value'];
    }
}
