<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

/**
 * A class holding a string value.
 */
class StringValue {

  /**
   * Human readable name.
   */
  public readonly string $name;

  public function __construct(public readonly string $value) {
    $this->name = 'string';
  }

}
