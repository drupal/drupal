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
   * @var array \Drupal\Core\Entity\EntityInterface[]
   */
  public $_relationship_entities = array();

}
