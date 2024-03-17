<?php

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the class resolver interface supporting class names and services.
 */
class ClassResolver implements ClassResolverInterface {

  use DependencySerializationTrait;

  /**
   * Constructs a new ClassResolver object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface|null $container
   *   The service container.
   */
  public function __construct(protected ?ContainerInterface $container = NULL) {
    if ($this->container === NULL) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $container argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3419963', E_USER_DEPRECATED);
      $this->container = \Drupal::getContainer();
    }
  }

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
      @trigger_error('Implementing \Symfony\Component\DependencyInjection\ContainerAwareInterface is deprecated in drupal:10.3.0 and it will be removed in drupal:11.0.0. Implement \Drupal\Core\DependencyInjection\ContainerInjectionInterface and use dependency injection instead. See https://www.drupal.org/node/3428661', E_USER_DEPRECATED);
      $instance->setContainer($this->container);
    }

    return $instance;
  }

  /**
   * Sets the service container.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0.
   *    Instead, you should pass the container as an argument in the
   *    __construct() method.
   *
   * @see https://www.drupal.org/node/3419963
   */
  public function setContainer(?ContainerInterface $container): void {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Instead, you should pass the container as an argument in the __construct() method. See https://www.drupal.org/node/3419963', E_USER_DEPRECATED);
    $this->container = $container;
  }

}
