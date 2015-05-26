<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\ClassResolver.
 */

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Implements the class resolver interface supporting class names and services.
 */
class ClassResolver implements ClassResolverInterface, ContainerAwareInterface {
  use DependencySerializationTrait;
  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function getInstanceFromDefinition($definition) {
    if ($this->container->has($definition)) {
      $instance = $this->container->get($definition);
    }
    else {
      if (!class_exists($definition)) {
        throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $definition));
      }

      if (is_subclass_of($definition, 'Drupal\Core\DependencyInjection\ContainerInjectionInterface')) {
        $instance = $definition::create($this->container);
      }
      else {
        $instance = new $definition();
      }
    }

    if ($instance instanceof ContainerAwareInterface) {
      $instance->setContainer($this->container);
    }

    return $instance;
  }

}
