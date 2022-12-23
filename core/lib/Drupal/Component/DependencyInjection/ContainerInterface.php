<?php

namespace Drupal\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface as BaseContainerInterface;

/**
 * The interface for Drupal service container classes.
 *
 * This interface extends Symfony's ContainerInterface and adds methods for
 * managing mappings of instantiated services to its IDs.
 */
interface ContainerInterface extends BaseContainerInterface {

  /**
   * Gets all defined service IDs.
   *
   * @return array
   *   An array of all defined service IDs.
   */
  public function getServiceIds();

  /**
   * Collect a mapping between service to ids.
   *
   * @return array
   *   Service ids keyed by a unique hash.
   *
   * @deprecated in drupal:9.5.1 and is removed from drupal:11.0.0. Use the
   *   'Drupal\Component\DependencyInjection\ReverseContainer' service instead.
   *
   * @see https://www.drupal.org/node/3327942
   */
  public function getServiceIdMappings(): array;

  /**
   * Generate a unique hash for a service object.
   *
   * @param object $object
   *   Object needing a unique hash.
   *
   * @return string
   *   A unique hash identifying the object.
   *
   * @deprecated in drupal:9.5.1 and is removed from drupal:11.0.0. Use the
   *   'Drupal\Component\DependencyInjection\ReverseContainer' service instead.
   *
   * @see https://www.drupal.org/node/3327942
   */
  public function generateServiceIdHash(object $object): string;

}
