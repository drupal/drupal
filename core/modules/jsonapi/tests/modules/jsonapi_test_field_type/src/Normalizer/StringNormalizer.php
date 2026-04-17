<?php

declare(strict_types=1);

namespace Drupal\jsonapi_test_field_type\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\serialization\Normalizer\FieldItemNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalizes string fields weirdly: replaces 'super' with 'NOT' and vice versa.
 */
class StringNormalizer extends FieldItemNormalizer implements DenormalizerInterface {

  /**
   * Normalizes data into a set of arrays/scalars.
   *
   * @param mixed $object
   *   Data to normalize.
   * @param string|null $format
   *   Format the normalization result will be encoded as.
   * @param array<string, mixed> $context
   *   Context options for the normalizer.
   *
   * @return array
   *   Normalized data.
   */
  public function normalize($object, $format = NULL, array $context = []): array {
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

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [StringItem::class => TRUE];
  }

}
