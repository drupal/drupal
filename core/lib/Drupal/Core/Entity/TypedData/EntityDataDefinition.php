<?php

namespace Drupal\Core\Entity\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * A typed data definition class for describing entities.
 */
class EntityDataDefinition extends ComplexDataDefinitionBase implements EntityDataDefinitionInterface {

  /**
   * Creates a new entity definition.
   *
   * @param string $entity_type_id
   *   (optional) The ID of the entity type, or NULL if the entity type is
   *   unknown. Defaults to NULL.
   * @param string $bundle
   *   (optional) The bundle of the entity type, or NULL if the bundle is
   *   unknown. Defaults to NULL.
   *
   * @return static
   */
  public static function create($entity_type_id = NULL, $bundle = NULL) {
    // If the entity type is known, use the derived definition.
    if (isset($entity_type_id)) {
      $data_type = "entity:{$entity_type_id}";

      // If a bundle was given, use the bundle-specific definition.
      if ($bundle) {
        $data_type .= ":{$bundle}";
      }

      // It's possible that the given entity type ID or bundle wasn't discovered
      // by the TypedData plugin manager and/or weren't created by the
      // EntityDeriver. In that case, this is a new definition and we'll just
      // create the definition from defaults by using an empty array.
      $values = \Drupal::typedDataManager()->getDefinition($data_type, FALSE);
      $definition = new static(is_array($values) ? $values : []);

      // Set the EntityType constraint using the given entity type ID.
      $definition->setEntityTypeId($entity_type_id);

      // If available, set the Bundle constraint.
      if ($bundle) {
        $definition->setBundles([$bundle]);
      }

      return $definition;
    }

    return new static([]);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($data_type) {
    $parts = explode(':', $data_type);
    if ($parts[0] != 'entity') {
      throw new \InvalidArgumentException('Data type must be in the form of "entity:ENTITY_TYPE:BUNDLE."');
    }
    return static::create($parts[1] ?? NULL, $parts[2] ?? NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      if ($entity_type_id = $this->getEntityTypeId()) {
        // Return an empty array for entities that are not content entities.
        $entity_type_class = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getClass();
        if (!in_array('Drupal\Core\Entity\FieldableEntityInterface', class_implements($entity_type_class))) {
          $this->propertyDefinitions = [];
        }
        else {
          // @todo Add support for handling multiple bundles.
          // See https://www.drupal.org/node/2169813.
          $bundles = $this->getBundles();
          if (is_array($bundles) && count($bundles) == 1) {
            $this->propertyDefinitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, reset($bundles));
          }
          else {
            $this->propertyDefinitions = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions($entity_type_id);
          }
        }
      }
      else {
        // No entity type given.
        $this->propertyDefinitions = [];
      }
    }
    return $this->propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataType() {
    $type = 'entity';
    if ($entity_type = $this->getEntityTypeId()) {
      $type .= ':' . $entity_type;
      // Append the bundle only if we know it for sure and it is not the default
      // bundle.
      if (($bundles = $this->getBundles()) && count($bundles) == 1) {
        $bundle = reset($bundles);
        if ($bundle != $entity_type) {
          $type .= ':' . $bundle;
        }
      }
    }
    return $type;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->definition['constraints']['EntityType'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityTypeId($entity_type_id) {
    return $this->addConstraint('EntityType', $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    $bundle = $this->definition['constraints']['Bundle'] ?? NULL;
    return is_string($bundle) ? [$bundle] : $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundles(?array $bundles = NULL) {
    if (isset($bundles)) {
      $this->addConstraint('Bundle', $bundles);
    }
    else {
      // Remove the constraint.
      unset($this->definition['constraints']['Bundle']);
    }
    return $this;
  }

}
