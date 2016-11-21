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
 * In the source phase, a set of data, called the row, is retrieved from the
 * data source, typically a database but it can be a CSV, JSON or XML file. The
 * row is sent to the process phase where it is transformed as needed by the
 * destination, or marked to be skipped. Processing can also determine that a
 * stub needs to be created, for example, if a term has a parent term that does
 * not yet exist. After processing the transformed row is passed to the
 * destination phase where it is loaded (saved) into the Drupal 8 site.
 *
 * The ETL process is configured by the migration plugin. The different phases:
 * source, process, and destination are also plugins, and are managed by the
 * Migration plugin. So there are four types of plugins in the migration
 * process: migration, source, process and destination.
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
 * \Drupal\migrate\Plugin\MigrateSourcePluginManager class. Source plugin
 * providers are determined by their and their parents namespaces.
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
 * Allows altering the list of discovered migration plugins.
 *
 * Modules are able to alter specific migrations structures or even remove or
 * append additional migrations to the discovery. For example, this
 * implementation filters out Drupal 6 migrations from the discovered migration
 * list. This is done by checking the migration tags.
 *
 * @param array[] $migrations
 *   An associative array of migrations keyed by migration ID. Each value is the
 *   migration array, obtained by decoding the migration YAML file and enriched
 *   with some meta information added during discovery phase, like migration
 *   'class', 'provider' or '_discovered_file_path'.
 *
 * @ingroup migration
 */
function hook_migration_plugins_alter(array &$migrations) {
  $migrations = array_filter($migrations, function (array $migration) {
    $tags = isset($migration['migration_tags']) ? (array) $migration['migration_tags'] : [];
    return !in_array('Drupal 6', $tags);
  });
}

/**
 * @} End of "addtogroup hooks".
 */
