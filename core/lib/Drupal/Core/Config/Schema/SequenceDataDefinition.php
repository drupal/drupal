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
    return $this->definition['orderby'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataType() {
    // TRICKY: this class extends ListDataDefinition, which always returns a
    // hardcoded "list". But this is a typed data type used in config schemas,
    // and hence many subtypes of it exists. The actual concrete subtype must
    // always be returned.
    // This effectively means skipping the parent implementation and matching
    // the grandparent implementation.
    // @see \Drupal\Core\TypedData\ListDataDefinition::setDataType()
    // @see \Drupal\Core\TypedData\ListDataDefinition::getDataType()
    return $this->definition['type'];
  }

}
