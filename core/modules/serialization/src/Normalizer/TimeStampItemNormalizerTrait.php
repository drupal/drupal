<?php

namespace Drupal\serialization\Normalizer;

use Symfony\Component\Serializer\Exception\UnexpectedValueException;

@trigger_error(__NAMESPACE__ . '\TimeStampItemNormalizerTrait is deprecated in Drupal 8.7.0 and will be removed in Drupal 9.0.0. Use \Drupal\serialization\Normalizer\TimestampNormalizer instead.', E_USER_DEPRECATED);

/**
 * A trait for TimestampItem normalization functionality.
 *
 * @deprecated in 8.7.0, use \Drupal\serialization\Normalizer\TimestampNormalizer instead.
 */
trait TimeStampItemNormalizerTrait {

  /**
   * Allowed timestamps formats for the denormalizer.
   *
   * The denormalizer allows deserialization to timestamps from three
   * different formats. Validation of the input data and creation of the
   * numerical timestamp value is handled with \DateTime::createFromFormat().
   * The list is chosen to be unambiguous and language neutral, but also common
   * for data interchange.
   *
   * @var string[]
   *
   * @see http://php.net/manual/datetime.createfromformat.php
   */
  protected $allowedFormats = [
    'UNIX timestamp' => 'U',
    'ISO 8601' => \DateTime::ISO8601,
    'RFC 3339' => \DateTime::RFC3339,
  ];

  /**
   * Processes normalized timestamp values to add a formatted date and format.
   *
   * @param array $normalized
   *   The normalized field data to process.
   * @return array
   *   The processed data.
   */
  protected function processNormalizedValues(array $normalized) {
    // Use a RFC 3339 timestamp with the time zone set to UTC to replace the
    // timestamp value.
    $date = new \DateTime();
    $date->setTimestamp($normalized['value']);
    $date->setTimezone(new \DateTimeZone('UTC'));
    $normalized['value'] = $date->format(\DateTime::RFC3339);
    // 'format' is not a property on TimestampItem fields. This is present to
    // assist consumers of this data.
    $normalized['format'] = \DateTime::RFC3339;

    return $normalized;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    // Loop through the allowed formats and create a TimestampItem from the
    // input data if it matches the defined pattern. Since the formats are
    // unambiguous (i.e., they reference an absolute time with a defined time
    // zone), only one will ever match.
    $timezone = new \DateTimeZone('UTC');

    // First check for a provided format.
    if (!empty($data['format']) && in_array($data['format'], $this->allowedFormats)) {
      $date = \DateTime::createFromFormat($data['format'], $data['value'], $timezone);
      return ['value' => $date->getTimestamp()];
    }
    // Otherwise, loop through formats.
    else {
      foreach ($this->allowedFormats as $format) {
        if (($date = \DateTime::createFromFormat($format, $data['value'], $timezone)) !== FALSE) {
          return ['value' => $date->getTimestamp()];
        }
      }
    }

    $format_strings = [];

    foreach ($this->allowedFormats as $label => $format) {
      $format_strings[] = "\"$format\" ($label)";
    }

    $formats = implode(', ', $format_strings);
    throw new UnexpectedValueException(sprintf('The specified date "%s" is not in an accepted format: %s.', $data['value'], $formats));
  }

}
