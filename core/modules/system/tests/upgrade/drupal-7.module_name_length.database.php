<?php

/**
 * @file
 * Database additions for \Drupal\system\Tests\Upgrade\ExistingModuleNameLengthUpgradePathTest.
 *
 * The drupal-7.bare.database.php file is imported before this dump, so the
 * two form the database structure expected in tests altogether.
 */

db_insert('system')
  ->fields(array(
    'filename' => 'modules/invalid_module_name_over_the_maximum_allowed_character_length.module',
    'name' => 'invalid_module_name_over_the_maximum_allowed_character_length',
    'type' => 'module',
    'status' => 1,
    'schema_version' => 0,
  ))
  ->execute();
