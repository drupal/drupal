<?php

declare(strict_types=1);

namespace Drupal\jsonapi_test_data_type\Normalizer;

use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalizes string data weirdly: replaces 'super' with 'NOT' and vice versa.
 */
class StringNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    return str_replace('super', 'NOT', $object->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []): mixed {
    return str_replace('NOT', 'super', $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [StringData::class => TRUE];
  }

}
