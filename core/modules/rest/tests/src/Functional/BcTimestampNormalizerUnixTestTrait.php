<?php

namespace Drupal\Tests\rest\Functional;

@trigger_error(__NAMESPACE__ . '\BcTimestampNormalizerUnixTestTrait is deprecated in Drupal 9.0.0 and will be removed before Drupal 10.0.0. Instead of BcTimestampNormalizerUnixTestTrait::formatExpectedTimestampItemValues(123456789), use (new \DateTime())->setTimestamp(123456789)->setTimezone(new \DateTimeZone("UTC"))->format(\DateTime::RFC3339), see https://www.drupal.org/node/2859657.', E_USER_DEPRECATED);

/**
 * Trait for ResourceTestBase subclasses formatting expected timestamp data.
 */
trait BcTimestampNormalizerUnixTestTrait {

  /**
   * Formats a UNIX timestamp.
   *
   * @param int $timestamp
   *   The timestamp value to format.
   *
   * @return string
   *   The formatted RFC3339 date string.
   *
   * @see \Drupal\serialization\Normalizer\TimestampItemNormalizer
   */
  protected function formatExpectedTimestampItemValues($timestamp) {
    $date = new \DateTime();
    $date->setTimestamp($timestamp);
    // Per \Drupal\Core\TypedData\Plugin\DataType\Timestamp::getDateTime(), they
    // default to string representations in the UTC timezone.
    $date->setTimezone(new \DateTimeZone('UTC'));

    // Format is also added to the expected return values.
    return [
      'value' => $date->format(\DateTime::RFC3339),
      'format' => \DateTime::RFC3339,
    ];
  }

}
