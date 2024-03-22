<?php

namespace Drupal\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface as BaseContainerInterface;

/**
 * The interface for Drupal service container classes.
 */
interface ContainerInterface extends BaseContainerInterface {

  /**
   * Gets all defined service IDs.
   *
   * @return array
   *   An array of all defined service IDs.
   */
  public function getServiceIds();

}
