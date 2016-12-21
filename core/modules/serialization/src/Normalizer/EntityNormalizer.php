<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalizes/denormalizes Drupal entity objects into an array structure.
 */
class EntityNormalizer extends ComplexDataNormalizer implements DenormalizerInterface {

  use FieldableEntityNormalizerTrait;

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = [EntityInterface::class];

  /**
   * Constructs an EntityNormalizer object.
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
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $entity_type_id = $this->determineEntityTypeId($class, $context);
    $entity_type_definition = $this->getEntityTypeDefinition($entity_type_id);

    // The bundle property will be required to denormalize a bundleable
    // fieldable entity.
    if ($entity_type_definition->hasKey('bundle') && $entity_type_definition->isSubclassOf(FieldableEntityInterface::class)) {
      // Get an array containing the bundle only. This also remove the bundle
      // key from the $data array.
      $bundle_data = $this->extractBundleData($data, $entity_type_definition);

      // Create the entity from bundle data only, then apply field values after.
      $entity = $this->entityManager->getStorage($entity_type_id)->create($bundle_data);

      $this->denormalizeFieldData($data, $entity, $format, $context);
    }
    else {
      // Create the entity from all data.
      $entity = $this->entityManager->getStorage($entity_type_id)->create($data);
    }

    // Pass the names of the fields whose values can be merged.
    // @todo https://www.drupal.org/node/2456257 remove this.
    $entity->_restSubmittedFields = array_keys($data);

    return $entity;
  }

}
