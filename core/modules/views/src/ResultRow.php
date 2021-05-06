<?php

namespace Drupal\views;

/**
 * A class representing a view result row.
 */
class ResultRow {

  /**
   * The entity for this result.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  // phpcs:ignore Drupal.Classes.PropertyDeclaration
  public $_entity = NULL;

  /**
   * An array of relationship entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  // phpcs:ignore Drupal.Classes.PropertyDeclaration
  public $_relationship_entities = [];

  /**
   * An incremental number which represents the row in the entire result.
   *
   * @var int
   */
  public $index;

  /**
   * Constructs a ResultRow object.
   *
   * @param array $values
   *   (optional) An array of values to add as properties on the object.
   */
  public function __construct(array $values = []) {
    foreach ($values as $key => $value) {
      $this->{$key} = $value;
    }
  }

  /**
   * Resets the _entity and _relationship_entities properties.
   */
  public function resetEntityData() {
    $this->_entity = NULL;
    $this->_relationship_entities = [];
  }

}
