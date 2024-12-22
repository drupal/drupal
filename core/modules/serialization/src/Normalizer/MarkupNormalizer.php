<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Component\Render\MarkupInterface;

/**
 * Normalizes MarkupInterface objects into a string.
 */
class MarkupNormalizer extends NormalizerBase {

  use SchematicNormalizerTrait;
  use JsonSchemaReflectionTrait;

  /**
   * {@inheritdoc}
   */
  public function doNormalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    return (string) $object;
  }

  /**
   * {@inheritdoc}
   */
  public function getNormalizationSchema(mixed $object, array $context = []): array {
    return $this->getJsonSchemaForMethod(
      $object,
      '__toString',
      [
        'type' => 'string',
        'description' => 'May contain HTML markup.',
      ]
    );
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
