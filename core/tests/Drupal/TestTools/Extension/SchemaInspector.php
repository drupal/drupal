<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension;

use Drupal\Core\Database\SchemaDefinition\Schema;
use Drupal\Core\Database\SchemaDefinition\SchemaDefinitionType;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides methods to access modules' schema.
 */
class SchemaInspector {

  /**
   * Returns the module's schema specification.
   *
   * This function can be used to retrieve a schema specification provided by
   * hook_schema(), so it allows you to derive your tables from existing
   * specifications.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $handler
   *   The module handler to use for calling schema hook.
   * @param string $module
   *   The module to which the table belongs.
   *
   * @return \Drupal\Core\Database\SchemaDefinition\Schema|array
   *   An array of schema definition provided by hook_schema().
   *
   * @see \hook_schema()
   */
  public static function getTablesSpecification(ModuleHandlerInterface $handler, string $module): Schema|array {
    if ($handler->loadInclude($module, 'install')) {
      $schema = $handler->invoke($module, 'schema') ?? [];
      if ($schema instanceof Schema) {
        assert($schema->type === SchemaDefinitionType::Module, 'Invalid schema definition type, must be SchemaDefinitionType::Module');
        assert($schema->name === $module, 'Invalid schema definition name, must be equal to the module name');
      }
      return $schema;
    }
    return [];
  }

  /**
   * Returns the module's schema table names.
   *
   * This function can be used to retrieve a schema specification provided by
   * hook_schema(), so it allows you to derive your tables from existing
   * specifications.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $handler
   *   The module handler to use for calling schema hook.
   * @param string $module
   *   The module to which the tables belong.
   *
   * @return list<string>
   *   A list of table names.
   *
   * @see \hook_schema()
   */
  public static function getTableNames(ModuleHandlerInterface $handler, string $module): array {
    $schema = self::getTablesSpecification($handler, $module);
    return $schema instanceof Schema ? $schema->tableNames() : array_keys($schema);
  }

}
