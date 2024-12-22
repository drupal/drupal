<?php

declare(strict_types=1);

namespace Drupal\serialization\Normalizer;

/**
 * Trait for normalizers which can also provide JSON Schema.
 *
 * To implement this trait, convert the existing normalizer's ::normalize()
 * method to ::doNormalize().
 *
 * Due to trait inheritance rules, this trait cannot be used with normalizers
 * which call parent::normalize() during normalization (will result in infinite
 * recursion). Instead, use SchematicNormalizerHelperTrait and conditionally
 * call ::getNormalizationSchema() in ::normalize(). See
 * DateTimeIso8601Normalizer::normalize() for an example.
 */
trait SchematicNormalizerTrait {

  use SchematicNormalizerHelperTrait;
  use SchematicNormalizerFallbackTrait;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    if ($format === 'json_schema') {
      return $this->getNormalizationSchema($object, $context);
    }
    return $this->doNormalize($object, $format, $context);
  }

  /**
   * Normalizes an object into a set of arrays/scalars.
   *
   * @param mixed $object
   *   Object to normalize.
   * @param string|null $format
   *   Format the normalization result will be encoded as.
   * @param array $context
   *   Context options for the normalizer.
   *
   * @return array|string|int|float|bool|\ArrayObject|null
   *   The normalization. An \ArrayObject is used to make sure an empty object
   *   is encoded as an object not an array.
   */
  abstract protected function doNormalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL;

  /**
   * {@inheritdoc}
   */
  protected function getNormalizationSchema(mixed $object, array $context = []): array {
    return $this->getJsonSchemaForMethod($this, 'doNormalize', ['$comment' => static::generateNoSchemaAvailableMessage($object)]);
  }

}
