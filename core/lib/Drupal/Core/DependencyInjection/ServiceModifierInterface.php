<?php

namespace Drupal\Core\DependencyInjection;

/**
 * Interface that service providers can implement to modify services.
 */
interface ServiceModifierInterface {

  /**
   * Modifies existing service definitions.
   *
   * @param ContainerBuilder $container
   *   The ContainerBuilder whose service definitions can be altered.
   */
  public function alter(ContainerBuilder $container);

}
