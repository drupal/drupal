<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\TypedData\EntityDataDefinition.
 */

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
   *
   * @return static
   */
  public static function create($entity_type_id = NULL) {
    $definition = new static(array());
    // Set the passed entity type.
    if (isset($entity_type_id)) {
      $definition->setEntityTypeId($entity_type_id);
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($data_type) {
    $parts = explode(':', $data_type);
    if ($parts[0] != 'entity') {
      throw new \InvalidArgumentException('Data type must be in the form of "entity:ENTITY_TYPE:BUNDLE."');
    }
    $definition = static::create();
    // Set the passed entity type and bundle.
    if (isset($parts[1])) {
      $definition->setEntityTypeId($parts[1]);
    }
    if (isset($parts[2])) {
      $definition->setBundles(array($parts[2]));
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      if ($entity_type_id = $this->getEntityTypeId()) {
        // @todo: Add support for handling multiple bundles.
        // See https://drupal.org/node/2169813.
        $bundles = $this->getBundles();
        if (is_array($bundles) && count($bundles) == 1) {
          $this->propertyDefinitions = \Drupal::entityManager()->getFieldDefinitions($entity_type_id, reset($bundles));
        }
        else {
          $this->propertyDefinitions = \Drupal::entityManager()->getBaseFieldDefinitions($entity_type_id);
        }
      }
      else {
        // No entity type given.
        $this->propertyDefinitions = array();
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
      // Append the bundle only if we know it for sure.
      if (($bundles = $this->getBundles()) && count($bundles) == 1) {
        $type .= ':' . reset($bundles);
      }
    }
    return $type;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->getConstraint('EntityType');
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
    $bundle = $this->getConstraint('Bundle');
    return is_string($bundle) ? array($bundle) : $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundles(array $bundles = NULL) {
    if (isset($bundles)) {
      $this->addConstraint('Bundle', $bundles);
    }
    else {
      // Remove the constraint.
      $constraints = $this->getConstraints();
      unset($constraints['Bundle']);
      $this->setConstraints($constraints);
    }
    return $this;
  }

}
