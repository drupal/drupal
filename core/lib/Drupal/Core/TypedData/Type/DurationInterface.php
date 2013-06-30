<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\BinaryInterface.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\PrimitiveInterface;

/**
 * Interface for binary data.
 */
interface DurationInterface extends PrimitiveInterface {

  /**
   * Returns the duration.
   *
   * @return \DateInterval|null
   *   A DateInterval object or NULL if there is no duration.
   *
   * @throws \Exception
   */
  public function getDuration();

  /**
   * Sets the duration.
   *
   * @param \DateInterval $duration
   *   A duration to set.
   */
  public function setDuration(\DateInterval $duration);

}
