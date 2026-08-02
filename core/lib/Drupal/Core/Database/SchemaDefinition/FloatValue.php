<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

/**
 * A class holding a float value.
 */
class FloatValue {

  /**
   * Human readable name.
   */
  public readonly string $name;

  public function __construct(public readonly float $value) {
    $this->name = 'float';
  }

}
