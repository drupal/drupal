<?php

namespace Drupal\Tests\rest\Functional;

/**
 * Trait for ResourceTestBase subclasses formatting expected timestamp data.
 */
trait BcTimestampNormalizerUnixTestTrait {

  /**
   * Formats a UNIX timestamp.
   *
   * Depending on the 'bc_timestamp_normalizer_unix' setting. The return will be
   * an RFC3339 date string or the same timestamp that was passed in.
   *
   * @param int $timestamp
   *   The timestamp value to format.
   *
   * @return string|int
   *   The formatted RFC3339 date string or UNIX timestamp.
   *
   * @see \Drupal\serialization\Normalizer\TimestampItemNormalizer
   */
  protected function formatExpectedTimestampItemValues($timestamp) {
    // If the setting is enabled, just return the timestamp as-is now.
    if ($this->config('serialization.settings')->get('bc_timestamp_normalizer_unix')) {
      return ['value' => $timestamp];
    }

    // Otherwise, format the date string to the same that
    // \Drupal\serialization\Normalizer\TimestampItemNormalizer will produce.
    $date = new \DateTime();
    $date->setTimestamp($timestamp);
    $date->setTimezone(new \DateTimeZone('UTC'));

    // Format is also added to the expected return values.
    return [
      'value' => $date->format(\DateTime::RFC3339),
      'format' => \DateTime::RFC3339,
    ];
  }

}
