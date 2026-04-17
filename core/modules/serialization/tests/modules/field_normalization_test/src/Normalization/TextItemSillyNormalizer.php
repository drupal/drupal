<?php

declare(strict_types=1);

namespace Drupal\field_normalization_test\Normalization;

use Drupal\serialization\Normalizer\FieldItemNormalizer;
use Drupal\text\Plugin\Field\FieldType\TextItemBase;

/**
 * A test TextItem normalizer to test denormalization.
 */
class TextItemSillyNormalizer extends FieldItemNormalizer {

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
    $data = parent::normalize($object, $format, $context);
    $data['value'] .= '::silly_suffix';
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    $value = parent::constructValue($data, $context);
    $value['value'] = str_replace('::silly_suffix', '', $value['value']);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [TextItemBase::class => TRUE];
  }

}
