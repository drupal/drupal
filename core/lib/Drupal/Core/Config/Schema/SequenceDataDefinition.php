<?php

namespace Drupal\Core\Config\Schema;

use Drupal\Core\TypedData\ListDataDefinition;

/**
 * A typed data definition class for defining sequences in configuration.
 */
class SequenceDataDefinition extends ListDataDefinition {

  /**
   * Gets the description of how the sequence should be sorted.
   *
   * Only the top level of the array should be sorted. Top-level keys should be
   * discarded when using 'value' sorting. If the sequence is an associative
   * array 'key' sorting is recommended, if not 'value' sorting is recommended.
   *
   * @return string|null
   *   May be 'key' (to sort by key), 'value' (to sort by value, discarding
   *   keys), or NULL (if the schema does not describe how the sequence should
   *   be sorted).
   */
  public function getOrderBy() {
    return isset($this->definition['orderby']) ? $this->definition['orderby'] : NULL;
  }

}
