<?php

namespace Drupal\Core\DependencyInjection;

/**
 * Interface that all service providers must implement.
 */
interface ServiceProviderInterface {

  /**
   * Registers services to the container.
   *
   * @param ContainerBuilder $container
   *   The ContainerBuilder to register services to.
   */
  public function register(ContainerBuilder $container);

}
