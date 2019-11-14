<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalizes/denormalizes Drupal entity objects into an array structure.
 */
class EntityNormalizer extends ComplexDataNormalizer implements DenormalizerInterface {

  use FieldableEntityNormalizerTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = EntityInterface::class;

  /**
   * Constructs an EntityNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeRepositoryInterface $entity_type_repository, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeRepository = $entity_type_repository;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $entity_type_id = $this->determineEntityTypeId($class, $context);
    $entity_type_definition = $this->getEntityTypeDefinition($entity_type_id);

    // The bundle property will be required to denormalize a bundleable
    // fieldable entity.
    if ($entity_type_definition->entityClassImplements(FieldableEntityInterface::class)) {
      // Extract bundle data to pass into entity creation if the entity type uses
      // bundles.
      if ($entity_type_definition->hasKey('bundle')) {
        // Get an array containing the bundle only. This also remove the bundle
        // key from the $data array.
        $create_params = $this->extractBundleData($data, $entity_type_definition);
      }
      else {
        $create_params = [];
      }

      // Create the entity from bundle data only, then apply field values after.
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($create_params);

      $this->denormalizeFieldData($data, $entity, $format, $context);
    }
    else {
      // Create the entity from all data.
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($data);
    }

    // Pass the names of the fields whose values can be merged.
    // @todo https://www.drupal.org/node/2456257 remove this.
    $entity->_restSubmittedFields = array_keys($data);

    return $entity;
  }

}
