<?php

namespace Drupal\hal\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Drupal\serialization\Normalizer\TimeStampItemNormalizerTrait;

/**
 * Converts values for TimestampItem to and from common formats for hal.
 */
class TimestampItemNormalizer extends FieldItemNormalizer {

  use TimeStampItemNormalizerTrait;

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = TimestampItem::class;

  /**
   * {@inheritdoc}
   */
  protected function normalizedFieldValues(FieldItemInterface $field_item, $format, array $context) {
    $normalized = parent::normalizedFieldValues($field_item, $format, $context);
    return $this->processNormalizedValues($normalized);
  }

}
