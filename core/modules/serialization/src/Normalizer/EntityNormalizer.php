<?php

/**
 * @file
 * Contains \Drupal\serialization\Normalizer\EntityNormalizer.
 */

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalizes/denormalizes Drupal entity objects into an array structure.
 */
class EntityNormalizer extends ComplexDataNormalizer implements DenormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = array('Drupal\Core\Entity\EntityInterface');

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
    // Get the entity type ID while letting context override the $class param.
    $entity_type_id = !empty($context['entity_type']) ? $context['entity_type'] : $this->entityManager->getEntityTypeFromClass($class);

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type_definition */
    // Get the entity type definition.
    $entity_type_definition = $this->entityManager->getDefinition($entity_type_id, FALSE);

    // Don't try to create an entity without an entity type id.
    if (!$entity_type_definition) {
      throw new UnexpectedValueException(sprintf('The specified entity type "%s" does not exist. A valid etnity type is required for denormalization', $entity_type_id));
    }

    // The bundle property will be required to denormalize a bundleable entity.
    if ($entity_type_definition->hasKey('bundle')) {
      $bundle_key = $entity_type_definition->getKey('bundle');
      // Get the base field definitions for this entity type.
      $base_field_definitions = $this->entityManager->getBaseFieldDefinitions($entity_type_id);

      // Get the ID key from the base field definition for the bundle key or
      // default to 'value'.
      $key_id = isset($base_field_definitions[$bundle_key]) ? $base_field_definitions[$bundle_key]->getFieldStorageDefinition()->getMainPropertyName() : 'value';

      // Normalize the bundle if it is not explicitly set.
      $data[$bundle_key] = isset($data[$bundle_key][0][$key_id]) ? $data[$bundle_key][0][$key_id] : (isset($data[$bundle_key]) ? $data[$bundle_key] : NULL);

      // Get the bundle entity type from the entity type definition.
      $bundle_type_id = $entity_type_definition->getBundleEntityType();
      $bundle_types = $bundle_type_id ? $this->entityManager->getStorage($bundle_type_id)->getQuery()->execute() : [];

      // Make sure a bundle has been provided.
      if (!is_string($data[$bundle_key])) {
        throw new UnexpectedValueException('A string must be provided as a bundle value.');
      }

      // Make sure the submitted bundle is a valid bundle for the entity type.
      if ($bundle_types && !in_array($data[$bundle_key], $bundle_types)) {
        throw new UnexpectedValueException(sprintf('"%s" is not a valid bundle type for denormalization.', $data[$bundle_key]));
      }
    }

    // Create the entity from data.
    $entity = $this->entityManager->getStorage($entity_type_id)->create($data);

    // Pass the names of the fields whose values can be merged.
    // @todo https://www.drupal.org/node/2456257 remove this.
    $entity->_restSubmittedFields = array_keys($data);

    return $entity;
  }
}
