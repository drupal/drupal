<?php

declare(strict_types=1);

namespace Drupal\serialization\Serializer;

/**
 * Interface for JSON schema provider.
 */
interface JsonSchemaProviderSerializerInterface {

  /**
   * Convenience method to get a JSON schema.
   *
   * Unlike calling ::normalize() with $format of 'json_schema' directly, this
   * method always returns a schema, even if it's empty.
   *
   * @param mixed $object
   *   Object or interface/class name for which to retrieve a schema.
   * @param array $context
   *   Normalization context.
   *
   * @return array
   *   Schema.
   */
  public function getJsonSchema(mixed $object, array $context): array;

}
