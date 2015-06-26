<?php

/**
 * @file
 * Contains \Drupal\serialization\EntityResolver\UuidReferenceInterface.
 */

namespace Drupal\serialization\EntityResolver;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Interface for extracting UUID from entity reference data when denormalizing.
 */
interface UuidReferenceInterface extends NormalizerInterface {

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
