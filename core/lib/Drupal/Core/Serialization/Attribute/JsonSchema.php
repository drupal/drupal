<?php

declare(strict_types=1);

namespace Drupal\Core\Serialization\Attribute;

/**
 * Attribute for methods to express the JSON Schema of its return value.
 *
 * This attribute may be repeated to define multiple potential types.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class JsonSchema {

  /**
   * Constructor.
   *
   * @param array $schema
   *   Schema.
   */
  public function __construct(
    public readonly array $schema = [],
  ) {
  }

  /**
   * Get a JSON Schema type definition array.
   *
   * @return array
   *   Type definition.
   */
  public function getJsonSchema(): array {
    return $this->schema;
  }

}
