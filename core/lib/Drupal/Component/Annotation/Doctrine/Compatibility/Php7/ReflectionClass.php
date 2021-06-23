<?php
// phpcs:ignoreFile

namespace Drupal\Component\Annotation\Doctrine\Compatibility\Php7;

use ReflectionException;

trait ReflectionClass
{
    /**
     * {@inheritDoc}
     */
    public function getConstants()
    {
        throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function newInstance($args)
    {
        throw new ReflectionException('Method not implemented');
    }
}
