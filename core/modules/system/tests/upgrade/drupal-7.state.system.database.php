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
  ->keys(array('name' => 'node_cron_views_scale'))
  ->fields(array('value' => serialize(1.0 / 2000)))
  ->execute();
db_merge('variable')
  ->keys(array('name' => 'statistics_day_timestamp'))
  ->fields(array('value' => serialize(1352070595)))
  ->execute();
db_merge('variable')
  ->keys(array('name' => 'tracker_index_nid'))
  ->fields(array('value' => serialize(0)))
  ->execute();
db_merge('variable')
  ->keys(array('name' => 'update_last_check'))
  ->fields(array('value' => serialize(1304208000)))
  ->execute();
db_merge('variable')
  ->keys(array('name' => 'update_last_email_notification'))
  ->fields(array('value' => serialize(1304208000)))
  ->execute();
db_merge('variable')
  ->keys(array('name' => 'node_access_needs_rebuild'))
  ->fields(array('value' => serialize(TRUE)))
  ->execute();
db_merge('variable')
  ->keys(array('name' => 'node_cron_last'))
  ->fields(array('value' => serialize(1304208001)))
  ->execute();
db_merge('variable')
  ->keys(array('name' => 'cron_last'))
  ->fields(array('value' => serialize(1304208002)))
  ->execute();
db_merge('variable')
  ->keys(array('name' => 'cron_key'))
  ->fields(array('value' => serialize('kdm95qppDDlyZrcUOx453YwQqDA4DNmxi4VQcxzFU9M')))
  ->execute();
db_merge('variable')
  ->keys(array('name' => 'drupal_private_key'))
  ->fields(array('value' => serialize('G38mKqASKus8VGMkMzVuXImYbzspCQ1iRT2iEZpMYmQ')))
  ->execute();
db_merge('variable')
  ->keys(array('name' => 'node_cron_comments_scale'))
  ->fields(array('value' => serialize(1.0 / 1000)))
  ->execute();
