<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

/**
 * Enumeration of cases for schema source.
 *
 * It represents the Drupal feature that is providing a collection of schema
 * elements (only tables at the moment) that need to be available on the
 * database.
 */
enum SchemaDefinitionType: string {

  // Schemas provided by modules via hook_schema() implementations.
  case Module = 'module';

  // Schemas provided by storage interfaces.
  case Storage = 'storage';

  // Schemas provided by entities.
  case Entity = 'entity';

  // Schemas provided by migration.
  case Migrate = 'migrate';

  // Schemas provided by test classes for test purposes.
  case Test = 'test';

}
