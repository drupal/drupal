<?php

/**
 * @file
 * Fixture file to test filter_post_update_consolidate_filter_config().
 *
 * @see https://www.drupal.org/project/drupal/issues/3404431
 * @see \Drupal\Tests\filter\Functional\FilterFormatConsolidateFilterConfigUpdateTest
 * @see filter_post_update_consolidate_filter_config()
 */

use Drupal\Core\Database\Database;

$db = Database::getConnection();

$format = unserialize($db->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'filter.format.plain_text')
  ->execute()
  ->fetchField());

unset($format['filters']['filter_autop']['id']);
unset($format['filters']['filter_html_escape']['provider']);
unset($format['filters']['filter_url']['id']);
unset($format['filters']['filter_url']['provider']);

$db->update('config')
  ->fields(['data' => serialize($format)])
  ->condition('collection', '')
  ->condition('name', 'filter.format.plain_text')
  ->execute();
