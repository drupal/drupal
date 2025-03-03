<?php

declare(strict_types=1);

namespace Drupal\serialization\Serializer;

use Drupal\serialization\Normalizer\SchematicNormalizerFallbackTrait;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

/**
 * Trait for normalizing the JSON schema.
 */
trait JsonSchemaProviderSerializerTrait {

  use SchematicNormalizerFallbackTrait;

  /**
   * {@inheritdoc}
   */
  public function getJsonSchema(mixed $object, array $context): array {
    try {
      $normalizer_schema = $this->normalize($object, 'json_schema', $context);
    }
    catch (NotNormalizableValueException) {
      $normalizer_schema = ['$comment' => static::generateNoSchemaAvailableMessage($object)];
    }
    return $normalizer_schema;
  }

}
