<?php

/**
 * @file
 * Contains \Drupal\serialization\EntityResolver\UuidResolver
 */

namespace Drupal\serialization\EntityResolver;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Resolves entities from data that contains an entity UUID.
 */
class UuidResolver implements EntityResolverInterface {

  /**
   * Implements \Drupal\serialization\EntityResolver\EntityResolverInterface::resolve().
   */
  public function resolve(NormalizerInterface $normalizer, $data, $entity_type) {
    // The normalizer is what knows the specification of the data being
    // deserialized. If it can return a UUID from that data, and if there's an
    // entity with that UUID, then return its ID.
    if (($normalizer instanceof UuidReferenceInterface) && $uuid = $normalizer->getUuid($data)) {
      if ($entity = entity_load_by_uuid($entity_type, $uuid)) {
        return $entity->id();
      }
    }
    return NULL;
  }

}
