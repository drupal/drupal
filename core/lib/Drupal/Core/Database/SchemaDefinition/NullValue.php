<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

/**
 * A class holding a null value.
 */
class NullValue {

  /**
   * Human readable name.
   */
  public readonly string $name;

  /**
   * A null value.
   *
   * @var null
   */
  public readonly null $value;

  public function __construct() {
    $this->name = 'null';
    $this->value = NULL;
  }

}
