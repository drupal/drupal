<?php

declare(strict_types=1);

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Serialization\Attribute\JsonSchema;

/**
 * Interface for using reflection with the JSON object.
 */
trait JsonSchemaReflectionTrait {

  /**
   * Get a JSON Schema based on method reflection.
   *
   * @param object $object
   *   Object to reflect.
   * @param string $method
   *   Method to reflect.
   * @param array $fallback
   *   Fallback. Defaults to an empty array, which is a matches-all schema.
   * @param bool $nullable
   *   If a schema is returned from reflection, whether to add a null option.
   *
   * @return array
   *   JSON Schema.
   */
  protected function getJsonSchemaForMethod(mixed $object, string $method, array $fallback = [], bool $nullable = FALSE): array {
    $schemas = [];
    if ((is_object($object) || class_exists($object)) && method_exists($object, $method)) {
      $reflection = new \ReflectionMethod($object, $method);
      $schemas = $reflection->getAttributes(JsonSchema::class);
    }
    if (count($schemas) === 0) {
      return $fallback;
    }
    $schemas = array_values(array_filter([
      ...array_map(fn ($schema) => $schema->newInstance()->getJsonSchema(), $schemas),
      $nullable ? ['type' => 'null'] : NULL,
    ]));
    return count($schemas) === 1
      ? current($schemas)
      : ['oneOf' => $schemas];
  }

}
