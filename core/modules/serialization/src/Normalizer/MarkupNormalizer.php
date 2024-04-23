<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Component\Render\MarkupInterface;

/**
 * Normalizes MarkupInterface objects into a string.
 */
class MarkupNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    return (string) $object;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      MarkupInterface::class => TRUE,
    ];
  }

}
