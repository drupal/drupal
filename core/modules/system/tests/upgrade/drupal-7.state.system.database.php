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
  ->key(array('name' => 'update_last_check'))
  ->fields(array('value' => serialize(1304208000)))
  ->execute();
db_merge('variable')
  ->key(array('name' => 'update_last_email_notification'))
  ->fields(array('value' => serialize(1304208000)))
  ->execute();
db_merge('variable')
  ->key(array('name' => 'node_access_needs_rebuild'))
  ->fields(array('value' => serialize(TRUE)))
  ->execute();
db_merge('variable')
  ->key(array('name' => 'node_cron_last'))
  ->fields(array('value' => serialize(1304208001)))
  ->execute();
db_merge('variable')
  ->key(array('name' => 'cron_last'))
  ->fields(array('value' => serialize(1304208002)))
  ->execute();
