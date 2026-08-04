<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

/**
 * Interface for objects describing database elements.
 */
interface SchemaDefinitionInterface {

  /**
   * Converts the object to legacy array-based specifications.
   *
   * @return array
   *   The legacy array-based specification.
   *
   * @internal
   */
  public function toArray(): array;

}
