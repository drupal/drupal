<?php

/**
 * @file
 * Contains \Drupal\views\ResultRow.
 */

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
  public $_entity = NULL;

  /**
   * An array of relationship entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  public $_relationship_entities = array();

  /**
   * Constructs a ResultRow object.
   *
   * @param array $values
   *   (optional) An array of values to add as properties on the object.
   */
  public function __construct(array $values = array()) {
    foreach ($values as $key => $value) {
      $this->{$key} = $value;
    }
  }

}
