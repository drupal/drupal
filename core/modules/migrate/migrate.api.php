<?php

/**
 * @file
 * Hooks provided by the Migrate module.
 */

use Drupal\migrate\Entity\MigrationInterface;
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
 * (ETL) process. For historical reasons, in the Drupal migration tool the
 * extract phase is called "source", the transform phase is called "process",
 * and the load phase is called "destination".
 *
 * Source, process, and destination phases are each provided by plugins. Source
 * plugins extract data from a data source in "rows", containing "properties".
 * Each row is handed off to one or more series of process plugins, where each
 * series operates to transform the row data into one result property. After all
 * the properties are processed, the resulting row is handed off to a
 * destination plugin, which saves the data.
 *
 * The Migrate module provides process plugins for common operations (setting
 * default values, mapping values, etc.), and destination plugins for Drupal
 * core objects (configuration, entity, URL alias, etc.). The Migrate Drupal
 * module provides source plugins to extract data from various versions of
 * Drupal. Custom and contributed modules can provide additional plugins; see
 * the @link plugin_api Plugin API topic @endlink for generic information about
 * providing plugins, and sections below for details about the plugin types.
 *
 * The configuration of migrations is stored in configuration entities, which
 * list the IDs and configurations of the plugins that are involved. See
 * @ref sec_entity below for details. To migrate an entire site, you'll need to
 * create a migration manifest; see @ref sec_manifest for details.
 *
 * https://www.drupal.org/node/2127611 has more complete information on the
 * Migration API, including information on load plugins, which are only used
 * in Drupal 6 migration.
 *
 * @section sec_source Source plugins
 * Migration source plugins implement
 * \Drupal\migrate\Plugin\MigrateSourceInterface and usually extend
 * \Drupal\migrate\Plugin\migrate\source\SourcePluginBase. They are annotated
 * with \Drupal\migrate\Annotation\MigrateSource annotation, and must be in
 * namespace subdirectory Plugin\migrate\source under the namespace of the
 * module that defines them. Migration source plugins are managed by the
 * \Drupal\migrate\Plugin\MigratePluginManager class.
 *
 * @section sec_process Process plugins
 * Migration process plugins implement
 * \Drupal\migrate\Plugin\MigrateProcessInterface and usually extend
 * \Drupal\migrate\ProcessPluginBase. They are annotated
 * with \Drupal\migrate\Annotation\MigrateProcessPlugin annotation, and must be
 * in namespace subdirectory Plugin\migrate\process under the namespace of the
 * module that defines them. Migration process plugins are managed by the
 * \Drupal\migrate\Plugin\MigratePluginManager class.
 *
 * @section sec_destination Destination plugins
 * Migration destination plugins implement
 * \Drupal\migrate\Plugin\MigrateDestinationInterface and usually extend
 * \Drupal\migrate\Plugin\migrate\destination\DestinationBase. They are
 * annotated with \Drupal\migrate\Annotation\MigrateDestination annotation, and
 * must be in namespace subdirectory Plugin\migrate\destination under the
 * namespace of the module that defines them. Migration destination plugins
 * are managed by the
 * \Drupal\migrate\Plugin\MigrateDestinationPluginManager class.
 *
 * @section sec_entity Migration configuration entities
 * The definition of how to migrate each type of data is stored in configuration
 * entities. The migration configuration entity class is
 * \Drupal\migrate\Entity\Migration, with interface
 * \Drupal\migrate\Entity\MigrationInterface; the configuration schema can be
 * found in the migrate.schema.yml file. Migration configuration consists of IDs
 * and configuration for the source, process, and destination plugins, as well
 * as information on dependencies. Process configuration consists of sections,
 * each of which defines the series of process plugins needed for one
 * destination property. You can find examples of migration configuration files
 * in the core/modules/migrate_drupal/config/install directory.
 *
 * @section sec_manifest Migration manifests
 * You can run a migration with the "drush migrate-manifest" command, providing
 * a migration manifest file. This file lists the configuration names of the
 * migrations you want to execute, as well as any dependencies they have (you
 * can find these in the "migration_dependencies" sections of the individual
 * configuration files). For example, to migrate blocks from a Drupal 6 site,
 * you would list:
 * @code
 * # Migrate blocks from Drupal 6 to 8
 * - d6_filter_format
 * - d6_custom_block
 * - d6_block
 * @endcode
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
