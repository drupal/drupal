<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinitionInterface;
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
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

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
    return !empty($context['entity_type']) ? $context['entity_type'] : $this->getEntityTypeRepository()->getEntityTypeFromClass($class);
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
    $entity_type_definition = $this->getEntityTypeManager()->getDefinition($entity_type_id, FALSE);

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
    $base_field_definitions = $this->getEntityFieldManager()->getBaseFieldDefinitions($entity_type_definition->id());

    // Get the ID key from the base field definition for the bundle key or
    // default to 'value'.
    $key_id = isset($base_field_definitions[$bundle_key]) ? $base_field_definitions[$bundle_key]->getFieldStorageDefinition()->getMainPropertyName() : 'value';

    // Normalize the bundle if it is not explicitly set.
    $bundle_value = $data[$bundle_key][0][$key_id] ?? ($data[$bundle_key] ?? NULL);
    // Unset the bundle from the data.
    unset($data[$bundle_key]);

    // Get the bundle entity type from the entity type definition.
    $bundle_type_id = $entity_type_definition->getBundleEntityType();
    $bundle_types = $bundle_type_id ? $this->getEntityTypeManager()->getStorage($bundle_type_id)->getQuery()->execute() : [];

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

  /**
   * Returns the entity type repository.
   *
   * @return \Drupal\Core\Entity\EntityTypeRepositoryInterface
   *   The entity type repository.
   */
  protected function getEntityTypeRepository() {
    return $this->entityTypeRepository;
  }

  /**
   * Returns the entity field manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   *   The entity field manager.
   */
  protected function getEntityFieldManager() {
    return $this->entityFieldManager;
  }

  /**
   * Returns the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * Build the field item value using the incoming data.
   *
   * Most normalizers that extend this class can simply use this method to
   * construct the denormalized value without having to override denormalize()
   * and re-implementing its validation logic or its call to set the field
   * value.
   *
   * It's recommended to not override this and instead provide a (de)normalizer
   * at the DataType level.
   *
   * @param mixed $data
   *   The incoming data for this field item.
   * @param array $context
   *   The context passed into the Normalizer.
   *
   * @return mixed
   *   The value to use in Entity::setValue().
   */
  protected function constructValue($data, $context) {
    $field_item = $context['target_instance'];

    // Get the property definitions.
    assert($field_item instanceof FieldItemInterface);
    $field_definition = $field_item->getFieldDefinition();
    $item_definition = $field_definition->getItemDefinition();
    assert($item_definition instanceof FieldItemDataDefinitionInterface);
    $property_definitions = $item_definition->getPropertyDefinitions();

    $serialized_property_names = $this->getCustomSerializedPropertyNames($field_item);
    $denormalize_property = function ($property_name, $property_value, $property_value_class, $context) use ($serialized_property_names) {
      if ($this->serializer->supportsDenormalization($property_value, $property_value_class, NULL, $context)) {
        return $this->serializer->denormalize($property_value, $property_value_class, NULL, $context);
      }
      else {
        if (in_array($property_name, $serialized_property_names, TRUE)) {
          $property_value = serialize($property_value);
        }
        return $property_value;
      }
    };

    if (!is_array($data)) {
      $property_value = $data;
      $property_name = $item_definition->getMainPropertyName();
      $property_value_class = $property_definitions[$property_name]->getClass();
      return $denormalize_property($property_name, $property_value, $property_value_class, $context);
    }

    $data_internal = [];
    if (!empty($property_definitions)) {
      foreach ($property_definitions as $property_name => $property_definition) {
        // Not every property is required to be sent.
        if (!array_key_exists($property_name, $data)) {
          continue;
        }
        $property_value = $data[$property_name];
        $property_value_class = $property_definition->getClass();
        $data_internal[$property_name] = $denormalize_property($property_name, $property_value, $property_value_class, $context);
      }
    }
    else {
      $data_internal = $data;
    }

    return $data_internal;
  }

}
