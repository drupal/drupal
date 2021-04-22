<?php

namespace Drupal\TestTools\Extension;

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
   * @return array
   *   An array of schema definition provided by hook_schema().
   *
   * @see \hook_schema()
   */
  public static function getTablesSpecification(ModuleHandlerInterface $handler, string $module): array {
    if ($handler->loadInclude($module, 'install')) {
      return $handler->invoke($module, 'schema') ?? [];
    }
    return [];
  }

}
