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
   * Normalizes data into a set of arrays/scalars.
   *
   * @param object $object
   *   Data to normalize.
   * @param string|null $format
   *   Format the normalization result will be encoded as.
   * @param array<string, mixed> $context
   *   Context options for the normalizer.
   *
   * @return string
   *   The normalized data.
   */
  public function doNormalize($object, $format = NULL, array $context = []): string {
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
