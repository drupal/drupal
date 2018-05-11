<?php

namespace Drupal\serialization\EntityResolver;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Resolves entities from data that contains an entity UUID.
 */
class UuidResolver implements EntityResolverInterface {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a UuidResolver object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityRepositoryInterface $entity_repository) {
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(NormalizerInterface $normalizer, $data, $entity_type) {
    // The normalizer is what knows the specification of the data being
    // deserialized. If it can return a UUID from that data, and if there's an
    // entity with that UUID, then return its ID.
    if (($normalizer instanceof UuidReferenceInterface) && ($uuid = $normalizer->getUuid($data))) {
      if ($entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid)) {
        return $entity->id();
      }
    }
    return NULL;
  }

}
