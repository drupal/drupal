<?php

namespace Drupal\Core\TypedData\Type;

/**
 * Interface for durations.
 *
 * @ingroup typed_data
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
   * Returns the duration as an ISO 8601 ABNF string.
   *
   * @return string
   *   ABNF-formatted duration.
   *
   * @see https://datatracker.ietf.org/doc/html/rfc3339#appendix-A
   */
  public function getDurationAsIso8601Abnf(): string;

  /**
   * Sets the duration.
   *
   * @param \DateInterval $duration
   *   A duration to set.
   */
  public function setDuration(\DateInterval $duration);

}
