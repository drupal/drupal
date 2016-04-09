<?php

namespace Drupal\serialization\EntityResolver;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Resolves entities from data that contains an entity target ID.
 */
class TargetIdResolver implements EntityResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(NormalizerInterface $normalizer, $data, $entity_type) {
    if (isset($data['target_id'])) {
      return $data['target_id'];
    }
    return NULL;
  }

}
