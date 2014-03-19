<?php

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Row;

/**
 * @file
 * Hooks provided by the Migrate module.
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
 * hook_migrate_MIGRATION_ID_prepare_row is also available.
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
