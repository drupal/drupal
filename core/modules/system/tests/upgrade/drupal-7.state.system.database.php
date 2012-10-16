<?php

/**
 * @file
 * Database additions for state system upgrade tests.
 *
 * This dump only contains data and schema components relevant for system
 * functionality. The drupal-7.filled.bare.php file is imported before
 * this dump, so the two form the database structure expected in tests
 * altogether.
 */

// Update system settings to known values.
db_merge('variable')
  ->key(array('name' => 'install_time'))->fields(array('value' => serialize(1304208000)))
  ->execute();

// Add non-default system settings.
