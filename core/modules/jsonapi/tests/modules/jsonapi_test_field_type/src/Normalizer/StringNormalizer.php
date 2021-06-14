<?php

namespace Drupal\jsonapi_test_field_type\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\serialization\Normalizer\FieldItemNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalizes string fields weirdly: replaces 'super' with 'NOT' and vice versa.
 */
class StringNormalizer extends FieldItemNormalizer implements DenormalizerInterface {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = StringItem::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $data = parent::normalize($object, $format, $context);
    $data['value'] = str_replace('super', 'NOT', $data['value']);
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    $data = parent::constructValue($data, $context);
    $data['value'] = str_replace('NOT', 'super', $data['value']);
    return $data;
  }

}
