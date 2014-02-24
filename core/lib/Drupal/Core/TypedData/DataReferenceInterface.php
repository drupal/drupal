<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\DataReferenceInterface.
 */

namespace Drupal\Core\TypedData;

/**
 * Interface for typed data references.
 *
 * @see \Drupal\Core\TypedData\DataReferenceDefinitionInterface
 */
interface DataReferenceInterface  {

  /**
   * Gets the referenced data.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface|null
   *   The referenced typed data object, or NULL if the reference is unset.
   */
  public function getTarget();

  /**
   * Gets the identifier of the referenced data.
   *
   * @return int|string|null
   *   The identifier of the referenced data, or NULL if the reference is unset.
   */
  public function getTargetIdentifier();

}
