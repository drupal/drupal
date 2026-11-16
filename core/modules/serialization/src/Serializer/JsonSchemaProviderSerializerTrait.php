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
    if (!is_object($object)) {
      @trigger_error('Passing anything other than an object to the $object parameter in ' . __CLASS__ . '::' . __FUNCTION__ . '() is deprecated in drupal:12.1.0 and is removed from drupal:13.0.0. Pass one object per call instead. See https://www.drupal.org/node/3632615', E_USER_DEPRECATED);
    }
    try {
      $normalizer_schema = $this->normalize($object, 'json_schema', $context);
    }
    catch (NotNormalizableValueException) {
      $normalizer_schema = ['$comment' => static::generateNoSchemaAvailableMessage($object)];
    }
    return $normalizer_schema;
  }

}
