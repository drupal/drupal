<?php

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a trait which automatically updates dependencies from the container.
 *
 * Classes which trigger a container rebuild should point to the instances of
 * the services with the new container. Calling the ::updateDependencies()
 * method takes care of that.
 *
 * If the service depends on container parameters and if they can possibly
 * change then the service will need to handle this itself.
 */
trait UpdateDependenciesTrait {

  /**
   * Updates an object's external dependencies from the container.
   *
   * This method depends on \Drupal\Core\DependencyInjection\Container::get()
   * adding the _serviceId property to all services.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @see \Drupal\Core\DependencyInjection\Container
   */
  protected function updateDependencies(ContainerInterface $container) {
    $vars = get_object_vars($this);
    foreach ($vars as $key => $value) {
      if (is_object($value) && isset($value->_serviceId)) {
        $this->$key = $container->get($value->_serviceId);
      }
      // Special case the container, which might not have a service ID.
      elseif ($value instanceof ContainerInterface) {
        $this->$key = $container;
      }
    }
  }

}
