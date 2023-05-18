<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Converts values for datetime objects to RFC3339 and from common formats.
 *
 * @internal
 */
class DateTimeNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * Allowed datetime formats for the denormalizer.
   *
   * The list is chosen to be unambiguous and language neutral, but also common
   * for data interchange.
   *
   * @var string[]
   *
   * @see http://php.net/manual/en/datetime.createfromformat.php
   */
  protected $allowedFormats = [
    'RFC 3339' => \DateTime::RFC3339,
    'ISO 8601' => \DateTime::ISO8601,
  ];

  /**
   * The system's date configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $systemDateConfig;

  /**
   * Constructs a new DateTimeNormalizer instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->systemDateConfig = $config_factory->get('system.date');
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($datetime, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    assert($datetime instanceof DateTimeInterface);
    $drupal_date_time = $datetime->getDateTime();
    if ($drupal_date_time === NULL) {
      return $drupal_date_time;
    }
    return $drupal_date_time
      // Set an explicit timezone. Otherwise, timestamps may end up being
      // normalized using the user's preferred timezone. Which would result in
      // many variations and complex caching.
      ->setTimezone($this->getNormalizationTimezone())
      ->format(\DateTime::RFC3339);
  }

  /**
   * Gets the timezone to be used during normalization.
   *
   * @see ::normalize
   * @see \Drupal\Core\Datetime\DrupalDateTime::prepareTimezone()
   *
   * @returns \DateTimeZone
   *   The timezone to use.
   */
  protected function getNormalizationTimezone() {
    $default_site_timezone = $this->systemDateConfig->get('timezone.default');
    return new \DateTimeZone($default_site_timezone);
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []): mixed {
    // This only knows how to denormalize datetime strings and timestamps. If
    // something else is received, let validation constraints handle this.
    if (!is_string($data) && !is_numeric($data)) {
      return $data;
    }

    // Loop through the allowed formats and create a \DateTime from the
    // input data if it matches the defined pattern. Since the formats are
    // unambiguous (i.e., they reference an absolute time with a defined time
    // zone), only one will ever match.
    $allowed_formats = $context['datetime_allowed_formats'] ?? $this->allowedFormats;
    foreach ($allowed_formats as $format) {
      $date = \DateTime::createFromFormat($format, $data);
      $errors = \DateTime::getLastErrors();
      if ($date !== FALSE && empty($errors['errors']) && empty($errors['warnings'])) {
        return $date;
      }
    }

    $format_strings = [];

    foreach ($allowed_formats as $label => $format) {
      $format_strings[] = "\"$format\" ($label)";
    }

    $formats = implode(', ', $format_strings);
    throw new UnexpectedValueException(sprintf('The specified date "%s" is not in an accepted format: %s.', $data, $formats));
  }

  /**
   * {@inheritdoc}
   */
  public function hasCacheableSupportsMethod(): bool {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use getSupportedTypes() instead. See https://www.drupal.org/node/3359695', E_USER_DEPRECATED);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      DateTimeInterface::class => TRUE,
    ];
  }

}
