<?php

/**
 * @file
 * Database additions for comment tests. Used in CommentUpgradePathTest.
 *
 * This dump only contains some non-standard configuration for comment
 * functionality. The drupal-7.filled.database.php and
 * drupal-7.comment.database.php files are imported before this dump, so the
 * three together form the database structure expected in tests.
 */

// Set up variables needed for comment support.
$variables = array(
  'comment_default_mode_blog' => 0,
  'comment_default_per_page_blog' => 25,
  'comment_form_location_blog' => 0,
  'comment_anonymous_blog' => 1,
  'comment_subject_field_blog' => 0,
  'comment_preview_blog' => 0,
);
db_delete('variable')
  ->condition('name', array_keys($variables), 'IN')
  ->execute();

$query = db_insert('variable')->fields(array(
  'name',
  'value',
));
foreach ($variables as $key => $value) {
  $query->values(array(
    'name' => $key,
    'value' => serialize($value),
  ));
}
$query->execute();
