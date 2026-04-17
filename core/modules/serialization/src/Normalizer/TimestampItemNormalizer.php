<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Drupal\Core\TypedData\Plugin\DataType\Timestamp;

/**
 * Converts values for TimestampItem to and from common formats.
 *
 * Overrides FieldItemNormalizer to use
 * \Drupal\serialization\Normalizer\TimestampNormalizer
 *
 * Overrides FieldItemNormalizer to
 * - during normalization, add the 'format' key to assist consumers
 * - during denormalization, use
 *   \Drupal\serialization\Normalizer\TimestampNormalizer
 */
class TimestampItemNormalizer extends FieldItemNormalizer {

  /**
   * Normalizes data into a set of arrays/scalars.
   *
   * @param object $object
   *   Data to normalize.
   * @param string|null $format
   *   Format the normalization result will be encoded as.
   * @param array<string, mixed> $context
   *   Context options for the normalizer.
   *
   * @return array
   *   The normalized data.
   */
  public function normalize($object, $format = NULL, array $context = []): array {
    return parent::normalize($object, $format, $context) + [
      // 'format' is not a property on Timestamp objects. This is present to
      // assist consumers of this data.
      'format' => \DateTime::RFC3339,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    if (!empty($data['format'])) {
      $context['datetime_allowed_formats'] = [$data['format']];
    }
    return ['value' => $this->serializer->denormalize($data['value'], Timestamp::class, NULL, $context)];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      TimestampItem::class => TRUE,
    ];
  }

}
