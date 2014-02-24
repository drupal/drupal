<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\DataReferenceDefinitionInterface.
 */

namespace Drupal\Core\TypedData;

/**
 * Interface for typed data references.
 *
 * @see \Drupal\Core\TypedData\DataReferenceDefinition
 * @see \Drupal\Core\TypedData\DataReferenceInterface
 */
interface DataReferenceDefinitionInterface extends DataDefinitionInterface  {

  /**
   * Gets the data definition of the referenced data.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The data definition of the referenced data.
   */
  public function getTargetDefinition();

}
