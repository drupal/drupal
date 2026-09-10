<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

/**
 * Enumeration of cases for generated column.
 */
enum GeneratedColumnStorage {

  case Virtual;
  case Stored;

}
