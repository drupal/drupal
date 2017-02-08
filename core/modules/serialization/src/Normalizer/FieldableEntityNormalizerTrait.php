<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * A trait for providing fieldable entity normalization/denormalization methods.
 *
 * @todo Move this into a FieldableEntityNormalizer in Drupal 9. This is a trait
 *   used in \Drupal\serialization\Normalizer\EntityNormalizer to maintain BC.
 *   @see https://www.drupal.org/node/2834734
 */
trait FieldableEntityNormalizerTrait {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Determines the entity type ID to denormalize as.
   *
   * @param string $class
   *   The entity type class to be denormalized to.
   * @param array $context
   *   The serialization context data.
   *
   * @return string
   *   The entity type ID.
   */
  protected function determineEntityTypeId($class, $context) {
    // Get the entity type ID while letting context override the $class param.
    return !empty($context['entity_type']) ? $context['entity_type'] : $this->entityManager->getEntityTypeFromClass($class);
  }

  /**
   * Gets the entity type definition.
   *
   * @param string $entity_type_id
   *   The entity type ID to load the definition for.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The loaded entity type definition.
   *
   * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
   */
  protected function getEntityTypeDefinition($entity_type_id) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type_definition */
    // Get the entity type definition.
    $entity_type_definition = $this->entityManager->getDefinition($entity_type_id, FALSE);

    // Don't try to create an entity without an entity type id.
    if (!$entity_type_definition) {
      throw new UnexpectedValueException(sprintf('The specified entity type "%s" does not exist. A valid entity type is required for denormalization', $entity_type_id));
    }

    return $entity_type_definition;
  }

  /**
   * Denormalizes the bundle property so entity creation can use it.
   *
   * @param array $data
   *   The data being denormalized.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type_definition
   *   The entity type definition.
   *
   * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
   *
   * @return string
   *   The valid bundle name.
   */
  protected function extractBundleData(array &$data, EntityTypeInterface $entity_type_definition) {
    $bundle_key = $entity_type_definition->getKey('bundle');
    // Get the base field definitions for this entity type.
    $base_field_definitions = $this->entityManager->getBaseFieldDefinitions($entity_type_definition->id());

    // Get the ID key from the base field definition for the bundle key or
    // default to 'value'.
    $key_id = isset($base_field_definitions[$bundle_key]) ? $base_field_definitions[$bundle_key]->getFieldStorageDefinition()->getMainPropertyName() : 'value';

    // Normalize the bundle if it is not explicitly set.
    $bundle_value = isset($data[$bundle_key][0][$key_id]) ? $data[$bundle_key][0][$key_id] : (isset($data[$bundle_key]) ? $data[$bundle_key] : NULL);
    // Unset the bundle from the data.
    unset($data[$bundle_key]);

    // Get the bundle entity type from the entity type definition.
    $bundle_type_id = $entity_type_definition->getBundleEntityType();
    $bundle_types = $bundle_type_id ? $this->entityManager->getStorage($bundle_type_id)->getQuery()->execute() : [];

    // Make sure a bundle has been provided.
    if (!is_string($bundle_value)) {
      throw new UnexpectedValueException(sprintf('Could not determine entity type bundle: "%s" field is missing.', $bundle_key));
    }

    // Make sure the submitted bundle is a valid bundle for the entity type.
    if ($bundle_types && !in_array($bundle_value, $bundle_types)) {
      throw new UnexpectedValueException(sprintf('"%s" is not a valid bundle type for denormalization.', $bundle_value));
    }

    return [$bundle_key => $bundle_value];
  }

  /**
   * Denormalizes entity data by denormalizing each field individually.
   *
   * @param array $data
   *   The data to denormalize.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The fieldable entity to set field values for.
   * @param string $format
   *   The serialization format.
   * @param array $context
   *   The context data.
   */
  protected function denormalizeFieldData(array $data, FieldableEntityInterface $entity, $format, array $context) {
    foreach ($data as $field_name => $field_data) {
      $field_item_list = $entity->get($field_name);

      // Remove any values that were set as a part of entity creation (e.g
      // uuid). If the incoming field data is set to an empty array, this will
      // also have the effect of emptying the field in REST module.
      $field_item_list->setValue([]);
      $field_item_list_class = get_class($field_item_list);

      if ($field_data) {
        // The field instance must be passed in the context so that the field
        // denormalizer can update field values for the parent entity.
        $context['target_instance'] = $field_item_list;
        $this->serializer->denormalize($field_data, $field_item_list_class, $format, $context);
      }
    }
  }

}
