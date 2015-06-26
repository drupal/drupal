<?php

/**
 * @file
 * Contains \Drupal\serialization\EntityResolver\UuidResolver.
 */

namespace Drupal\serialization\EntityResolver;

use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Resolves entities from data that contains an entity UUID.
 */
class UuidResolver implements EntityResolverInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a UuidResolver object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(NormalizerInterface $normalizer, $data, $entity_type) {
    // The normalizer is what knows the specification of the data being
    // deserialized. If it can return a UUID from that data, and if there's an
    // entity with that UUID, then return its ID.
    if (($normalizer instanceof UuidReferenceInterface) && ($uuid = $normalizer->getUuid($data))) {
      if ($entity = $this->entityManager->loadEntityByUuid($entity_type, $uuid)) {
        return $entity->id();
      }
    }
    return NULL;
  }

}
