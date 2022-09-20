<?php

namespace Drupal\Component\DependencyInjection;

/**
 * A trait for service id hashing implementations.
 *
 * Handles delayed cache tag invalidations.
 */
trait ServiceIdHashTrait {

  /**
   * Implements \Drupal\Component\DependencyInjection\ContainerInterface::getServiceIdMappings()
   */
  public function getServiceIdMappings(): array {
    $mapping = [];
    foreach ($this->getServiceIds() as $service_id) {
      if ($this->initialized($service_id) && $service_id !== 'service_container') {
        $service = $this->get($service_id);
        if (is_object($service)) {
          $mapping[$this->generateServiceIdHash($service)] = $service_id;
        }
      }
    }
    return $mapping;
  }

  /**
   * Implements \Drupal\Component\DependencyInjection\ContainerInterface::generateServiceIdHash()
   */
  public function generateServiceIdHash(object $object): string {
    // Include class name as an additional namespace for the hash since
    // spl_object_hash's return can be recycled. This still is not a 100%
    // guarantee to be unique but makes collisions incredibly difficult and even
    // then the interface would be preserved.
    // @see https://php.net/spl_object_hash#refsect1-function.spl-object-hash-notes
    return hash('sha256', get_class($object) . spl_object_hash($object));
  }

}
