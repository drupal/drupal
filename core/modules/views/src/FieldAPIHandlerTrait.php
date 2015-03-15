<?php

/**
 * @file
 * Contains \Drupal\views\FieldAPIHandlerTrait.
 */

namespace Drupal\views;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * A trait containing helper methods for field definitions.
 */
trait FieldAPIHandlerTrait {

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * The field storage definition.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $fieldStorageDefinition;

  /**
   * Gets the field definition.
   *
   * A View works on an entity type across bundles, and thus only has access to
   * field storage definitions. In order to be able to use widgets and
   * formatters, we create a generic field definition out of that storage
   * definition.
   *
   * @see BaseFieldDefinition::createFromFieldStorageDefinition()
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition used by this handler.
   */
  protected function getFieldDefinition() {
    if (!$this->fieldDefinition) {
      $field_storage_config = $this->getFieldStorageDefinition();
      $this->fieldDefinition = BaseFieldDefinition::createFromFieldStorageDefinition($field_storage_config);
    }
    return $this->fieldDefinition;
  }

  /**
   * Gets the field storage configuration.
   *
   * @return \Drupal\field\FieldStorageConfigInterface
   *   The field storage definition used by this handler
   */
  protected function getFieldStorageDefinition() {
    if (!$this->fieldStorageDefinition) {
      $field_storage_definitions = $this->getEntityManager()->getFieldStorageDefinitions($this->definition['entity_type']);
      $this->fieldStorageDefinition = $field_storage_definitions[$this->definition['field_name']];
    }
    return $this->fieldStorageDefinition;
  }

  /**
   * Returns the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager service.
   */
  protected function getEntityManager() {
    if (!isset($this->entityManager)) {
      $this->entityManager = \Drupal::entityManager();
    }
    return $this->entityManager;
  }

}
