<?php

namespace Drupal\field_normalization_test\Normalization;

use Drupal\serialization\Normalizer\FieldItemNormalizer;
use Drupal\text\Plugin\Field\FieldType\TextItemBase;

/**
 * A test TextItem normalizer to test denormalization.
 */
class TextItemSillyNormalizer extends FieldItemNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = TextItemBase::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $data = parent::normalize($object, $format, $context);
    $data['value'] .= '::silly_suffix';
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    $value = parent::constructValue($data, $context);
    $value['value']  = str_replace('::silly_suffix', '', $value['value']);
    return $value;
  }

}
