<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\DurationInterface.
 */

namespace Drupal\Core\TypedData\Type;

/**
 * Interface for durations.
 */
interface DurationInterface {

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
