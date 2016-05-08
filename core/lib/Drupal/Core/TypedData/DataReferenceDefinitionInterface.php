<?php

namespace Drupal\Core\TypedData;

/**
 * Interface for typed data references.
 *
 * @see \Drupal\Core\TypedData\DataReferenceDefinition
 * @see \Drupal\Core\TypedData\DataReferenceInterface
 *
 * @ingroup typed_data
 */
interface DataReferenceDefinitionInterface extends DataDefinitionInterface {

  /**
   * Gets the data definition of the referenced data.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The data definition of the referenced data.
   */
  public function getTargetDefinition();

}
