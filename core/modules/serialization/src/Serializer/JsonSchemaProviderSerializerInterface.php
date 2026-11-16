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
   * @param object $object
   *   The object to get a schema for, such as a typed data object.
   * @param array $context
   *   Normalization context.
   *
   * @return array
   *   Schema.
   *
   * @todo Type $object as object before drupal:13.0.0.
   *   https://www.drupal.org/project/drupal/issues/3632107
   */
  public function getJsonSchema(mixed $object, array $context): array;

}
