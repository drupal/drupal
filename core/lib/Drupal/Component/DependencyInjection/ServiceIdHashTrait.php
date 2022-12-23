<?php

namespace Drupal\Component\DependencyInjection;

/**
 * A trait for service id hashing implementations.
 *
 * @deprecated in drupal:9.5.1 and is removed from drupal:11.0.0. Use the
 *   'Drupal\Component\DependencyInjection\ReverseContainer' service instead.
 *
 * @see https://www.drupal.org/node/3327942
 */
trait ServiceIdHashTrait {

  /**
   * Implements \Drupal\Component\DependencyInjection\ContainerInterface::getServiceIdMappings()
   */
  public function getServiceIdMappings(): array {
    @trigger_error(__METHOD__ . "() is deprecated in drupal:9.5.1 and is removed from drupal:11.0.0. Use the 'Drupal\Component\DependencyInjection\ReverseContainer' service instead. See https://www.drupal.org/node/3327942", E_USER_DEPRECATED);
    $mapping = [];
    foreach ($this->getServiceIds() as $service_id) {
      if ($this->initialized($service_id) && $service_id !== 'service_container') {
        $mapping[$this->generateServiceIdHash($this->get($service_id))] = $service_id;
      }
    }
    return $mapping;
  }

  /**
   * Implements \Drupal\Component\DependencyInjection\ContainerInterface::generateServiceIdHash()
   */
  public function generateServiceIdHash(object $object): string {
    @trigger_error(__METHOD__ . "() is deprecated in drupal:9.5.1 and is removed from drupal:11.0.0. Use the 'Drupal\Component\DependencyInjection\ReverseContainer' service instead. See https://www.drupal.org/node/3327942", E_USER_DEPRECATED);
    // Include class name as an additional namespace for the hash since
    // spl_object_hash's return can be recycled. This still is not a 100%
    // guarantee to be unique but makes collisions incredibly difficult and even
    // then the interface would be preserved.
    // @see https://php.net/spl_object_hash#refsect1-function.spl-object-hash-notes
    return hash('sha256', get_class($object) . spl_object_hash($object));
  }

}
