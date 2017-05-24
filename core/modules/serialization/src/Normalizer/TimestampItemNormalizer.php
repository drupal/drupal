<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * Converts values for TimestampItem to and from common formats.
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
  public function normalize($field_item, $format = NULL, array $context = []) {
    $data = parent::normalize($field_item, $format, $context);

    return $this->processNormalizedValues($data);
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    if (empty($data['value'])) {
      throw new InvalidArgumentException('No "value" attribute present');
    }

    return parent::denormalize($data, $class, $format, $context);
  }

}
