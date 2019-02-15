<?php

namespace Drupal\hal\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Drupal\Core\TypedData\Plugin\DataType\Timestamp;

/**
 * Converts values for TimestampItem to and from common formats for hal.
 *
 * Overrides FieldItemNormalizer to
 * - during normalization, add the 'format' key to assist consumers
 * - during denormalization, use
 *   \Drupal\serialization\Normalizer\TimestampNormalizer
 */
class TimestampItemNormalizer extends FieldItemNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = TimestampItem::class;

  /**
   * {@inheritdoc}
   */
  protected function normalizedFieldValues(FieldItemInterface $field_item, $format, array $context) {
    return parent::normalizedFieldValues($field_item, $format, $context) + [
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

}
