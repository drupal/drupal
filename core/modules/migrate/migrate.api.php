<?php

/**
 * @file
 * Hooks provided by the Migrate module.
 */

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Row;

/**
 * @defgroup migration Migration API
 * @{
 * Overview of the Migration API, which migrates data into Drupal.
 *
 * @section overview Overview of migration
 * Migration is an
 * @link http://wikipedia.org/wiki/Extract,_transform,_load Extract, Transform, Load @endlink
 * (ETL) process. In the Drupal migration API the extract phase is called
 * "source", the transform phase is called "process", and the load phase is
 * called "destination". It is important to understand that the "load" in ETL
 * means to load data into storage, while traditionally Drupal uses "load" to
 * mean load data from storage into memory.
 *
 * Source, process, and destination phases are each provided by plugins.
 * Source plugins extract data from a data source in "rows", containing
 * "properties". Each row is handed off to one or more process plugins which
 * transform the row's properties. After all the properties are processed, the
 * resulting row is handed off to a destination plugin, which saves the data.
 *
 * A source plugin, one or more process plugins, and a destination plugin are
 * brought together to extract, transform, and load (in the ETL sense) a specific
 * type of data by a migration plugin.
 *
 * @section sec_migrations Migration plugins
 * Migration plugin definitions are stored in a module's 'migrations' directory.
 * For backwards compatibility we also scan the 'migration_templates' directory.
 * Examples of migration plugin definitions can be found in
 * 'core/modules/action/migration_templates'. The plugin class is
 * \Drupal\migrate\Plugin\Migration, with interface
 * \Drupal\migrate\Plugin\MigrationInterface. Migration plugins are managed by
 * the \Drupal\migrate\Plugin\MigrationPluginManager class. Migration plugins
 * are only available if the providers of their source plugins are installed.
 *
 * @section sec_source Source plugins
 * Migration source plugins implement
 * \Drupal\migrate\Plugin\MigrateSourceInterface and usually extend
 * \Drupal\migrate\Plugin\migrate\source\SourcePluginBase. They are annotated
 * with \Drupal\migrate\Annotation\MigrateSource annotation, and must be in
 * namespace subdirectory Plugin\migrate\source under the namespace of the
 * module that defines them. Migration source plugins are managed by the
 * \Drupal\migrate\Plugin\MigratePluginManager class. Source plugin providers
 * are determined by their and their parents namespaces.
 *
 * @section sec_process Process plugins
 * Migration process plugins implement
 * \Drupal\migrate\Plugin\MigrateProcessInterface and usually extend
 * \Drupal\migrate\ProcessPluginBase. They are annotated
 * with \Drupal\migrate\Annotation\MigrateProcessPlugin annotation, and must be
 * in namespace subdirectory Plugin\migrate\process under the namespace of the
 * module that defines them. Migration process plugins are managed by the
 * \Drupal\migrate\Plugin\MigratePluginManager class. The Migrate module
 * provides process plugins for common operations (setting default values,
 * mapping values, etc.).
 *
 * @section sec_destination Destination plugins
 * Migration destination plugins implement
 * \Drupal\migrate\Plugin\MigrateDestinationInterface and usually extend
 * \Drupal\migrate\Plugin\migrate\destination\DestinationBase. They are
 * annotated with \Drupal\migrate\Annotation\MigrateDestination annotation, and
 * must be in namespace subdirectory Plugin\migrate\destination under the
 * namespace of the module that defines them. Migration destination plugins
 * are managed by the \Drupal\migrate\Plugin\MigrateDestinationPluginManager
 * class. The Migrate module provides destination plugins for Drupal core
 * objects (configuration and entity).
 *
 * @section sec_more_info More information
 * @link https://www.drupal.org/node/2127611 Migration API documentation. @endlink
 *
 * @see update_api
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows adding data to a row before processing it.
 *
 * For example, filter module used to store filter format settings in the
 * variables table which now needs to be inside the filter format config
 * file. So, it needs to be added here.
 *
 * hook_migrate_MIGRATION_ID_prepare_row() is also available.
 *
 * @ingroup migration
 */
function hook_migrate_prepare_row(Row $row, MigrateSourceInterface $source, MigrationInterface $migration) {
  if ($migration->id() == 'd6_filter_formats') {
    $value = $source->getDatabase()->query('SELECT value FROM {variable} WHERE name = :name', array(':name' => 'mymodule_filter_foo_' . $row->getSourceProperty('format')))->fetchField();
    if ($value) {
      $row->setSourceProperty('settings:mymodule:foo', unserialize($value));
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
