<?php

/**
 * @file
 * Contains \Drupal\serialization\EntityResolver\UuidReferenceInterface
 */

namespace Drupal\serialization\EntityResolver;

/**
 * Interface for extracting UUID from entity reference data when denormalizing.
 */
interface UuidReferenceInterface {

  /**
   * Get the uuid from the data array.
   *
   * @param array $data
   *   The data, as was passed into the Normalizer.
   *
   * @return string
   *   A UUID.
   */
  public function getUuid($data);

}
