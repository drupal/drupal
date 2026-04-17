<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Normalizes typed data objects into strings or arrays.
 */
class TypedDataNormalizer extends NormalizerBase {

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
   * @return array|string|int|float|bool|\ArrayObject<mixed, mixed>|null
   *   \ArrayObject is used to make sure an empty object is encoded as an
   *   object not an array.
   */
  public function doNormalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    $this->addCacheableDependency($context, $object);
    $value = $object->getValue();
    // Support for stringable value objects: avoid numerous custom normalizers.
    if (is_object($value) && method_exists($value, '__toString')) {
      $value = (string) $value;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizationSchema(mixed $object, array $context = []): array {
    assert($object instanceof TypedDataInterface);
    $value = $object->getValue();
    $nullable = !$object->getDataDefinition()->isRequired();
    // Match the special-cased logic in ::normalize().
    if (is_object($value) && method_exists($value, '__toString')) {
      return $nullable
        ? ['oneOf' => ['string', 'null']]
        : ['type' => 'string'];
    }
    return $this->getJsonSchemaForMethod(
      $object,
      'getValue',
      ['$comment' => static::generateNoSchemaAvailableMessage($object)],
      $nullable
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      TypedDataInterface::class => TRUE,
    ];
  }

}
